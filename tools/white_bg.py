"""Flatten the studio backdrop of a hero photo to pure white.

The catalogue cards blend photos with ``mix-blend-mode: multiply``, so any
off-white / vignetted backdrop shows up as a grey panel inside the card.
This estimates the backdrop as a smooth field from the bright, low-gradient
pixels, then divides it out so the backdrop lands on #ffffff while the product
and its soft contact shadow keep their own shading.
"""
import numpy as np
import cv2


def _norm_conv(img, mask, sigma):
    """Normalised convolution, evaluated on a quarter-scale copy for speed."""
    h, w = img.shape[:2]
    sc = 0.25
    small_img = cv2.resize(img, (max(1, int(w * sc)), max(1, int(h * sc))), interpolation=cv2.INTER_AREA)
    small_mask = cv2.resize(mask.astype(np.float32), (max(1, int(w * sc)), max(1, int(h * sc))), interpolation=cv2.INTER_AREA)
    num = cv2.GaussianBlur(small_img * small_mask[..., None], (0, 0), sigma * sc)
    den = cv2.GaussianBlur(small_mask, (0, 0), sigma * sc)[..., None]
    field = num / np.maximum(den, 1e-4)
    return cv2.resize(field, (w, h), interpolation=cv2.INTER_LINEAR)


def flatten(path_in, path_out, target=255.0, quality=94, blur_sigma=None):
    bgr = cv2.imread(path_in, cv2.IMREAD_COLOR)
    img = bgr.astype(np.float32)
    h, w = img.shape[:2]
    sigma = blur_sigma or max(h, w) / 5.0

    g = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    smooth = cv2.GaussianBlur(g, (0, 0), 3)
    grad = np.abs(cv2.Sobel(smooth, cv2.CV_32F, 1, 0, ksize=3)) + \
           np.abs(cv2.Sobel(smooth, cv2.CV_32F, 0, 1, ksize=3))
    # the backdrop is bright, locally flat AND at some distance from any
    # product edge, so the object itself can never be mistaken for backdrop
    edges = (grad > 12).astype(np.uint8)
    edges = cv2.dilate(edges, np.ones((11, 11), np.uint8))
    backdrop = ((smooth > 205) & (edges == 0)).astype(np.uint8)
    # keep only backdrop connected to the frame border
    n, lab = cv2.connectedComponents(backdrop, connectivity=4)
    border = np.concatenate([lab[0], lab[-1], lab[:, 0], lab[:, -1]])
    labels = np.unique(border[border > 0])
    keep = np.isin(lab, labels).astype(np.uint8)
    field = _norm_conv(img, keep, sigma)
    field = np.clip(field, 190, 255)

    weight = cv2.GaussianBlur(keep.astype(np.float32), (0, 0), 6)[..., None]
    gain = np.clip(target / field, 1.0, 1.35)
    out = img * (1.0 + (gain - 1.0) * weight)
    # snap only genuine backdrop pixels (bright, deep inside the mask) to pure
    # white; the soft contact shadow and every product pixel are left alone
    snap = (weight[..., 0] > 0.80) & (img.min(axis=2) > 233)
    snapw = snap.astype(np.float32)[..., None] * weight
    out = out * (1.0 - snapw) + target * snapw
    out = np.clip(out, 0, 255).astype(np.uint8)
    cv2.imwrite(path_out, out, [cv2.IMWRITE_JPEG_QUALITY, quality])
    return out.shape


if __name__ == '__main__':
    import sys
    flatten(sys.argv[1], sys.argv[2])
