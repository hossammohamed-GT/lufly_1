<?php

declare(strict_types=1);

/** @var Core\View\View $view */
$view->pushStyle('frontend/vendor/leaflet/leaflet.css');
$view->pushScript('frontend/vendor/leaflet/leaflet.js');
$view->pushDeferredStyle('frontend/home/agents-map/agents-map.css');
$view->pushScript('frontend/home/agents-map/agents-map.js');

$network = (array) config('home-agents', []);
$factory = (array) ($network['factory'] ?? []);
$agents = (array) ($network['agents'] ?? []);
$deliveredMarkets = (array) ($network['delivered_markets'] ?? []);
$routesConfig = (array) config('home-routes', []);

$agentPoints = [];
foreach ($agents as $key => $point) {
    if (is_array($point)) {
        $point['type'] = 'agent';
        $agentPoints[$key] = $point;
    }
}
foreach ($deliveredMarkets as $key => $point) {
    if (is_array($point)) {
        $point['type'] = 'delivered';
        $agentPoints[$key] = $point;
    }
}

$getCountryName = static function (string $key, array $point): string {
    if (!empty($point['country_name'])) {
        return (string) $point['country_name'];
    }
    $country = (string) ($point['country'] ?? $key);
    $transKey = 'home.agents_country_' . $country;
    $translated = trans($transKey);
    if ($translated !== $transKey) {
        return $translated;
    }
    return ucwords(str_replace(['_', '-'], ' ', $country));
};

$getMapLabel = static function (string $key, array $point) use ($getCountryName): string {
    if (!empty($point['label'])) {
        return (string) $point['label'];
    }
    $country = (string) ($point['country'] ?? $key);
    $transKey = 'home.agents_map_label_' . $country;
    $translated = trans($transKey);
    if ($translated !== $transKey) {
        return $translated;
    }
    return $getCountryName($key, $point);
};

$getCoordinate = static function (array $point, string $axis, float $default): float {
    $value = (float) ($point[$axis] ?? $default);
    $min = $axis === 'latitude' ? -85.0 : -180.0;
    $max = $axis === 'latitude' ? 85.0 : 180.0;

    return max($min, min($max, $value));
};
$factoryCoordinates = [
    'latitude' => $getCoordinate($factory, 'latitude', 37.0662),
    'longitude' => $getCoordinate($factory, 'longitude', 37.3833),
];
$firstAgent = array_key_first($agentPoints);
?>
<?php if ($agentPoints !== []): ?>
<section class="agents-map-section scroll-section" id="global-agents" aria-labelledby="agents-map-title">
    <div class="container">
        <header class="agents-map-heading scroll-reveal">
            <div>
                <span class="section-tag"><?= e(trans('home.agents_tag')) ?></span>
                <h2 class="section-title" id="agents-map-title"><?= e(trans('home.agents_title')) ?></h2>
                <p class="agents-map-intro"><?= e(trans('home.agents_desc')) ?></p>
            </div>
            <span class="agents-map-count">
                <span aria-hidden="true"></span>
                <?= e(trans('home.agents_network_label')) ?>
            </span>
        </header>

        <div class="agents-map-layout">
            <div class="agents-map-visual"
                 id="agents-world-map"
                 data-agents-map
                 data-map-reset-label="<?= e(trans('home.agents_map_reset')) ?>"
                 data-map-loading="<?= e(trans('home.agents_map_loading')) ?>"
                 data-map-error="<?= e(trans('home.agents_map_error')) ?>"
                 data-map-factory-latitude="<?= e((string) $factoryCoordinates['latitude']) ?>"
                 data-map-factory-longitude="<?= e((string) $factoryCoordinates['longitude']) ?>"
                 data-map-factory-label="<?= e(trans('home.agents_factory')) ?>"
                 data-map-routes="<?= e(json_encode($routesConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                 aria-label="<?= e(trans('home.agents_map_desc')) ?>">
                <?php foreach ($agentPoints as $key => $agent):
                    $countryName = $getCountryName($key, $agent);
                    $mapLabel = $getMapLabel($key, $agent);
                    $pinType = (string) ($agent['type'] ?? 'agent');
                ?>
                    <button class="agents-map-location-data"
                            type="button"
                            data-agent-pin="<?= e($key) ?>"
                            data-pin-type="<?= e($pinType) ?>"
                            data-latitude="<?= e((string) $getCoordinate($agent, 'latitude', 0)) ?>"
                            data-longitude="<?= e((string) $getCoordinate($agent, 'longitude', 0)) ?>"
                            aria-controls="agent-card-<?= e($key) ?>"
                            aria-pressed="<?= $key === $firstAgent ? 'true' : 'false' ?>"
                            aria-label="<?= e(trans('home.agents_pin_label', ['country' => $countryName])) ?>">
                        <?= e($mapLabel) ?>
                    </button>
                <?php endforeach; ?>
                <span class="agents-map-location-data"
                      hidden
                      data-map-factory
                      data-latitude="<?= e((string) $factoryCoordinates['latitude']) ?>"
                      data-longitude="<?= e((string) $factoryCoordinates['longitude']) ?>"
                      data-label="<?= e(trans('home.agents_factory_map_label')) ?>"></span>
                <p class="agents-map-status" data-map-status role="status" hidden></p>
            </div>

            <aside class="agents-map-details" aria-label="<?= e(trans('home.agents_details_label')) ?>" aria-live="polite">
                <div class="agents-map-details-top">
                    <span class="agents-map-details-index"><?= e(trans('home.agents_details_eyebrow')) ?></span>
                    <span class="agents-map-details-signal" aria-hidden="true"><i></i><i></i><i></i></span>
                </div>
                <?php foreach ($agentPoints as $key => $agent):
                    $pinType = (string) ($agent['type'] ?? 'agent');
                    $countryName = $getCountryName($key, $agent);
                    $name = trim((string) ($agent['name'] ?? ''));
                    if ($name === '') {
                        $name = $pinType === 'delivered'
                            ? ($agent['project'] ?? trans('home.agents_delivered_default'))
                            : trans('home.agents_name_default');
                    }
                    $city = trim((string) ($agent['city'] ?? ''));
                    $address = trim((string) ($agent['address'] ?? ''));
                    $phone = trim((string) ($agent['phone'] ?? ''));
                    $email = trim((string) ($agent['email'] ?? ''));
                    $coverage = trim((string) ($agent['coverage'] ?? ''));
                    $contactPerson = trim((string) ($agent['contact_person'] ?? ''));
                    $description = trim((string) ($agent['description'] ?? ''));
                    $project = trim((string) ($agent['project'] ?? ''));
                    $hasContactDetails = $contactPerson !== '' || $phone !== '' || $email !== '';
                    $role = $pinType === 'delivered'
                        ? trans('home.agents_role_delivered')
                        : trans('home.agents_role');
                ?>
                    <div class="agents-map-card"
                         id="agent-card-<?= e($key) ?>"
                         data-agent-card="<?= e($key) ?>"
                         data-card-type="<?= e($pinType) ?>"
                         <?= $key === $firstAgent ? '' : 'hidden' ?>>
                        <span class="agents-map-card-country"><?= e($countryName) ?></span>
                        <h3><?= e($name) ?></h3>
                        <p class="agents-map-card-role"><?= e($role) ?></p>
                        <?php if ($description !== ''): ?>
                            <p class="agents-map-card-description"><?= e($description) ?></p>
                        <?php elseif (!$hasContactDetails && $pinType === 'agent'): ?>
                            <p class="agents-map-card-description"><?= e(trans('home.agents_info_placeholder')) ?></p>
                        <?php endif; ?>
                        <ul class="agents-map-contact">
                            <?php if ($project !== ''): ?>
                                <li><span><?= e(trans('home.agents_project_label')) ?></span><?= e($project) ?></li>
                            <?php endif; ?>
                            <?php if ($city !== ''): ?>
                                <li><span><?= e(trans('home.agents_city_label')) ?></span><?= e($city) ?></li>
                            <?php endif; ?>
                            <?php if ($address !== ''): ?>
                                <li><span><?= e(trans('home.agents_address_label')) ?></span><?= e($address) ?></li>
                            <?php endif; ?>
                            <?php if ($coverage !== ''): ?>
                                <li><span><?= e(trans('home.agents_coverage_label')) ?></span><?= e($coverage) ?></li>
                            <?php endif; ?>
                            <?php if ($contactPerson !== ''): ?>
                                <li><span><?= e(trans('home.agents_contact_person_label')) ?></span><?= e($contactPerson) ?></li>
                            <?php endif; ?>
                            <?php if ($phone !== ''): ?>
                                <li><span><?= e(trans('home.agents_phone_label')) ?></span><a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $phone) ?? '') ?>"><?= e($phone) ?></a></li>
                            <?php endif; ?>
                            <?php if ($email !== ''): ?>
                                <li><span><?= e(trans('home.agents_email_label')) ?></span><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
                <div class="agents-map-details-foot">
                    <span aria-hidden="true"></span>
                    <?= e(trans('home.agents_details_hint')) ?>
                </div>
            </aside>
        </div>

        <div class="agents-map-legend" aria-label="<?= e(trans('home.agents_legend_label')) ?>">
            <span class="legend-item"><i class="legend-swatch legend-swatch--factory" aria-hidden="true"></i><?= e(trans('home.agents_factory')) ?></span>
            <span class="legend-item"><i class="legend-swatch legend-swatch--europe-hub" aria-hidden="true"></i><?= e(trans('home.agents_europe_hub')) ?></span>
            <span class="legend-item"><i class="legend-swatch legend-swatch--dealer" aria-hidden="true"></i><?= e(trans('home.agents_role')) ?></span>
            <span class="legend-item"><i class="legend-swatch legend-swatch--priority" aria-hidden="true"></i><?= e(trans('home.agents_priority_markets')) ?></span>
            <span class="legend-item"><i class="legend-swatch legend-swatch--europe-route" aria-hidden="true"></i><?= e(trans('home.agents_europe_routes')) ?></span>
            <span class="legend-item"><i class="legend-swatch legend-swatch--subtle-route" aria-hidden="true"></i><?= e(trans('home.agents_subtle_routes')) ?></span>
            <span class="agents-map-legend-note"><?= e(trans('home.agents_legend_note')) ?></span>
        </div>
    </div>
</section>
<?php endif; ?>
