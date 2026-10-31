<?php

declare(strict_types=1);

/**
 * LUFLY Directional Routes Visualization Layer
 *
 * This configuration defines LUFLY's international geographic routes layer,
 * independent of the Authorized Dealers network.
 *
 * Route Categories:
 *  - Shared European line style: Royal blue, 2.1px, 6 5 dash, 0.88 opacity and the
 *    same quadratic curve used by Czech Republic -> Poland / Italy / Spain.
 *  - Origin A: Czech Republic -> European Markets (Poland, Italy, Spain) [Blue, 2.1px]
 *  - Origin B: Turkey -> Africa & Middle East:
 *      * Priority Highlighted Destinations: Egypt & UAE (Dubai) [Vibrant Gold/Amber, 3.5px]
 *      * Regional Destinations: Morocco, Algeria, Libya, Jordan, Saudi Arabia, Bahrain,
 *        Qatar, Oman, Kuwait [identical "europe" style to Origin A routes]
 *
 * Marker coloring for non-dealer destinations is driven by "marker_color":
 *  - "green": Middle East / North Africa pins (same pin as Egypt, Jordan, Saudi Arabia)
 *  - "blue":  European pins (same pin shape, blue)
 */
return [
    'origins' => [
        'czechia' => [
            'id' => 'czechia',
            'country' => 'czechia',
            'name' => 'Czech Republic',
            'city' => 'Prague',
            'hub_label' => 'European Hub · Czechia',
            'latitude' => 50.0755,
            'longitude' => 14.4378,
            'color' => '#2563eb',
            'routes' => [
                'poland' => [
                    'id' => 'poland',
                    'country' => 'poland',
                    'name' => 'Poland',
                    'city' => 'Warsaw',
                    'latitude' => 52.2297,
                    'longitude' => 21.0122,
                    'style' => 'europe',
                    'curve_bend' => -0.16,
                    'marker_color' => 'blue',
                ],
                'italy' => [
                    'id' => 'italy',
                    'country' => 'italy',
                    'name' => 'Italy',
                    'city' => 'Rome',
                    'latitude' => 41.9028,
                    'longitude' => 12.4964,
                    'style' => 'europe',
                    'curve_bend' => 0.14,
                    'marker_color' => 'blue',
                ],
                'spain' => [
                    'id' => 'spain',
                    'country' => 'spain',
                    'name' => 'Spain',
                    'city' => 'Madrid',
                    'latitude' => 40.4168,
                    'longitude' => -3.7038,
                    'style' => 'europe',
                    'curve_bend' => 0.18,
                    'marker_color' => 'blue',
                ],
            ],
        ],
        'turkey' => [
            'id' => 'turkey',
            'country' => 'turkey',
            'name' => 'Türkiye',
            'city' => 'Gaziantep',
            'hub_label' => 'Global Factory · Türkiye',
            'latitude' => 37.0662,
            'longitude' => 37.3833,
            'color' => '#b38345',
            'routes' => [
                // Highlighted Priority Markets (Section 5)
                'egypt' => [
                    'id' => 'egypt',
                    'country' => 'egypt',
                    'name' => 'Egypt',
                    'city' => 'Cairo',
                    'latitude' => 30.0444,
                    'longitude' => 31.2357,
                    'style' => 'highlighted',
                    'is_dealer' => true,
                    // Positive bend sweeps the arc above the straight line.
                    'curve_bend' => 0.16,
                ],
                'uae' => [
                    'id' => 'uae',
                    'country' => 'uae',
                    'name' => 'United Arab Emirates',
                    'city' => 'Dubai',
                    'latitude' => 25.2048,
                    'longitude' => 55.2708,
                    'style' => 'highlighted',
                    'is_dealer' => true,
                    'curve_bend' => -0.13,
                ],
                // Regional Africa & Middle East Markets (Section 6) — same line style as Europe
                'morocco' => [
                    'id' => 'morocco',
                    'country' => 'morocco',
                    'name' => 'Morocco',
                    'city' => 'Casablanca',
                    'latitude' => 33.5731,
                    'longitude' => -7.5898,
                    'style' => 'europe',
                    'is_dealer' => false,
                    'curve_bend' => -0.14,
                    'marker_color' => 'green',
                ],
                'algeria' => [
                    'id' => 'algeria',
                    'country' => 'algeria',
                    'name' => 'Algeria',
                    'city' => 'Algiers',
                    'latitude' => 36.7538,
                    'longitude' => 3.0588,
                    'style' => 'europe',
                    'is_dealer' => false,
                    'curve_bend' => 0.22,
                    'marker_color' => 'green',
                ],
                'libya' => [
                    'id' => 'libya',
                    'country' => 'libya',
                    'name' => 'Libya',
                    'city' => 'Tripoli',
                    'latitude' => 32.8872,
                    'longitude' => 13.1913,
                    'style' => 'europe',
                    'is_dealer' => false,
                    'curve_bend' => 0.14,
                    'marker_color' => 'green',
                ],
                'jordan' => [
                    'id' => 'jordan',
                    'country' => 'jordan',
                    'name' => 'Jordan',
                    'city' => 'Amman',
                    'latitude' => 31.9539,
                    'longitude' => 35.9106,
                    'style' => 'europe',
                    'is_dealer' => true,
                    'curve_bend' => 0.03,
                    'marker_color' => 'green',
                ],
                'saudi_arabia' => [
                    'id' => 'saudi_arabia',
                    'country' => 'saudi_arabia',
                    'name' => 'Saudi Arabia',
                    'city' => 'Riyadh',
                    'latitude' => 24.7136,
                    'longitude' => 46.6753,
                    'style' => 'europe',
                    'is_dealer' => true,
                    'curve_bend' => 0.22,
                    'marker_color' => 'green',
                ],
                'bahrain' => [
                    'id' => 'bahrain',
                    'country' => 'bahrain',
                    'name' => 'Bahrain',
                    'city' => 'Manama',
                    'latitude' => 26.2285,
                    'longitude' => 50.5860,
                    'style' => 'europe',
                    'is_dealer' => true,
                    'curve_bend' => 0.06,
                    'marker_color' => 'green',
                ],
                'qatar' => [
                    'id' => 'qatar',
                    'country' => 'qatar',
                    'name' => 'Qatar',
                    'city' => 'Doha',
                    'latitude' => 25.2854,
                    'longitude' => 51.5310,
                    'style' => 'europe',
                    'is_dealer' => true,
                    'curve_bend' => 0.21,
                    'marker_color' => 'green',
                ],
                'oman' => [
                    'id' => 'oman',
                    'country' => 'oman',
                    'name' => 'Oman',
                    'city' => 'Muscat',
                    'latitude' => 23.5880,
                    'longitude' => 58.3829,
                    'style' => 'europe',
                    'is_dealer' => true,
                    'curve_bend' => -0.22,
                    'marker_color' => 'green',
                ],
                'kuwait' => [
                    'id' => 'kuwait',
                    'country' => 'kuwait',
                    'name' => 'Kuwait',
                    'city' => 'Kuwait City',
                    'latitude' => 29.3759,
                    'longitude' => 47.9774,
                    'style' => 'europe',
                    'is_dealer' => false,
                    'curve_bend' => -0.03,
                    'marker_color' => 'green',
                ],
            ],
        ],
    ],
];
