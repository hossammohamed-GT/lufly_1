"""Put a product photo on a pure #ffffff backdrop without touching the product.

Two modes:

* ``flatten`` – for photos shot on a light, slightly tinted or vignetted
  backdrop. The backdrop is estimated as a smooth field from the bright,
  low-gradient pixels connected to the frame border; those pixels are then
  driven to pure white with a soft inward feather. Product pixels keep their
  original values exactly (weight is zero outside the backdrop mask), so
  nothing is re-lit, brightened or washed out.

Used by the catalogue cards, which blend photos with mix-blend-mode: multiply:
an off-white backdrop shows up there as a grey panel inside the card.

* ``extract`` – for photos shot on a uniform mid-grey or dark backdrop. The
  background is flood-filled from the frame border and replaced by white,
  keeping a soft 1px anti-aliased edge.
"""
import numpy as np
import cv2


def _norm_conv(img, mask, sigma):
    h, w = img.shape[:2]
    sc = 0.25
    small = cv2.resize(img, (max(1, int(w * sc)), max(1, int(h * sc))), interpolation=cv2.INTER_AREA)
    m = cv2.resize(mask.astype(np.float32), (max(1, int(w * sc)), max(1, int(h * sc))), interpolation=cv2.INTER_AREA)
    num = cv2.GaussianBlur(small * m[..., None], (0, 0), sigma * sc)
    den = cv2.GaussianBlur(m, (0, 0), sigma * sc)[..., None]
    field = num / np.maximum(den, 1e-4)
    return cv2.resize(field, (w, h), interpolation=cv2.INTER_LINEAR)


def _border_stats(img):
    h, w = img.shape[:2]
    b = 6
    edge = np.concatenate([img[:b].reshape(-1, 3), img[-b:].reshape(-1, 3),
                           img[:, :b].reshape(-1, 3), img[:, -b:].reshape(-1, 3)])
    return edge.mean(0), edge.std(0).mean()


def flatten(img, sigma=None):
    """Drive a light, uneven backdrop to pure white without re-lighting the product.

    The backdrop is modelled as a smooth field. Every pixel gets a *soft*
    opacity from how close it is to that field, so pixels that equal the
    backdrop become pure white, product pixels are left exactly as they were,
    and the transition is a smooth ramp rather than a hard-edged patch.
    """
    h, w = img.shape[:2]
    sigma = sigma or max(h, w) / 4.0
    f = img.astype(np.float32)
    g = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    smooth = cv2.GaussianBlur(g, (0, 0), 3)
    grad = np.abs(cv2.Sobel(smooth, cv2.CV_32F, 1, 0, ksize=3)) + \
           np.abs(cv2.Sobel(smooth, cv2.CV_32F, 0, 1, ksize=3))
    edges = cv2.dilate((grad > 10).astype(np.uint8), np.ones((7, 7), np.uint8))
    b = 6
    edge_px = np.concatenate([img[:b].reshape(-1, 3), img[-b:].reshape(-1, 3),
                              img[:, :b].reshape(-1, 3), img[:, -b:].reshape(-1, 3)])
    ref = np.median(edge_px, axis=0)
    close = (np.abs(f - ref) < 16).all(axis=2)
    mask = ((smooth > 170) & (edges == 0) & close).astype(np.uint8)
    n, lab = cv2.connectedComponents(mask, connectivity=4)
    if n < 2:
        return img
    border = np.concatenate([lab[0], lab[-1], lab[:, 0], lab[:, -1]])
    labels = np.unique(border[border > 0])
    if labels.size == 0:
        return img
    keep = np.isin(lab, labels).astype(np.uint8)
    if keep.mean() < 0.02:
        return img

    # fit a smooth quadratic surface to the backdrop pixels: it follows the
    # studio vignette all the way into the corners, which a blurred estimate
    # cannot do, and it never bends towards the product
    ys, xs = np.nonzero(keep)
    if xs.size < 200:
        return img
    step = max(1, xs.size // 40000)
    ys, xs = ys[::step], xs[::step]
    xn = xs / max(1, w - 1) - 0.5
    yn = ys / max(1, h - 1) - 0.5
    A = np.stack([np.ones_like(xn), xn, yn, xn * xn, xn * yn, yn * yn], axis=1)
    coef, *_ = np.linalg.lstsq(A, f[ys, xs], rcond=None)
    gx = np.linspace(-0.5, 0.5, w)[None, :]
    gy = np.linspace(-0.5, 0.5, h)[:, None]
    GX, GY = np.meshgrid(gx, gy)
    B = np.stack([np.ones_like(GX), GX, GY, GX * GX, GX * GY, GY * GY], axis=-1)
    field = np.clip(B @ coef, 170, 255)

    # soft opacity: 1 where the pixel matches the backdrop, 0 on the product
    diff = np.abs(f - field).max(axis=2)
    alpha = np.clip((16.0 - diff) / 10.0, 0.0, 1.0)
    alpha = cv2.GaussianBlur(alpha, (0, 0), 2.0)[..., None]

    # a pixel that is deep inside the backdrop goes all the way to 255; the
    # further it is from the field value, the more of its own tone it keeps
    pure = np.clip((8.0 - diff) / 6.0, 0.0, 1.0)**2
    pure = cv2.GaussianBlur(pure, (0, 0), 2.0)[..., None]
    lifted = np.clip(f * (255.0 / field), 0, 255)
    out = f * (1 - alpha) + lifted * alpha
    out = out * (1 - pure) + 255.0 * pure
    return np.clip(out, 0, 255).astype(np.uint8)


def extract(img, tol=26):
    """Return the image with a uniform mid/dark backdrop replaced by white."""
    h, w = img.shape[:2]
    blur = cv2.GaussianBlur(img, (0, 0), 1.2)
    mask = np.zeros((h + 2, w + 2), np.uint8)
    seeds = [(0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1)]
    filled = np.zeros((h, w), np.uint8)
    for sx, sy in seeds:
        m = mask.copy()
        cv2.floodFill(blur, m, (sx, sy), 255, (tol,) * 3, (tol,) * 3,
                      cv2.FLOODFILL_MASK_ONLY | cv2.FLOODFILL_FIXED_RANGE | 4)
        filled |= m[1:-1, 1:-1]
    alpha = 1.0 - cv2.GaussianBlur(filled.astype(np.float32), (0, 0), 0.8)
    alpha = np.clip(alpha, 0, 1)[..., None]
    out = img.astype(np.float32) * alpha + 255.0 * (1 - alpha)
    return np.clip(out, 0, 255).astype(np.uint8)


def white_background(path_in, path_out, quality=94):
    bgr = cv2.imread(path_in, cv2.IMREAD_COLOR)
    mean, std = _border_stats(bgr)
    if mean.min() > 232:
        out = flatten(bgr)
    elif std < 16 and mean.min() > 120:
        out = extract(bgr)
        out = flatten(out)
    else:
        out = bgr  # busy or dark photo backdrop: leave alone
    cv2.imwrite(path_out, out, [cv2.IMWRITE_JPEG_QUALITY, quality])
    return out.shape
