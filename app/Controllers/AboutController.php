<?php
declare(strict_types=1);

namespace App\Controllers;

final class AboutController
{
    public function show(): void
    {
        $title           = 'About — GeoAtlas Bangladesh';
        $metaDescription = 'About the Geography & Environment student and GIS enthusiast behind GeoAtlas Bangladesh.';

        // ---- Every value below is an EDITABLE PLACEHOLDER. ----
        // Search this file for "PLACEHOLDER" and replace with real details.
        // Do NOT add real credentials you would not want public.
        $profile = [
            'name'         => 'Your Name',                                            // PLACEHOLDER
            'role'         => 'Geography & Environment student · GIS enthusiast',     // PLACEHOLDER
            'location'     => 'Bangladesh',                                           // PLACEHOLDER
            'bio_short'    => 'I am a Geography and Environment student exploring Bangladesh through '
                            . 'geographic information systems, remote sensing, and cartography. '
                            . 'GeoAtlas Bangladesh is where I publish the maps and analyses I build.',
            'bio_long'     => [                                                       // PLACEHOLDER paragraphs
                'I am currently studying Geography and Environment and spend most of my time '
                . 'working with spatial data — from satellite imagery to field-collected points — '
                . 'to understand how the landscapes, rivers, and cities of Bangladesh are changing.',
                'This portfolio is a working atlas, not a finished statement. Each project is '
                . 'documented as it is completed, with sources, methods, and limitations stated plainly.',
            ],
            'education'    => [                                                       // PLACEHOLDER
                ['degree' => 'B.Sc. in Geography and Environment (in progress)',
                 'institution' => 'Your University', 'year' => '20XX–present'],
            ],
            'interests'    => [                                                       // PLACEHOLDER
                'Urban growth and heat',
                'River morphology and delta dynamics',
                'Land cover change and vegetation',
                'Accessibility and network analysis',
                'Cartographic design for public communication',
            ],
            'skills'       => [                                                       // PLACEHOLDER
                'GIS'               => ['QGIS', 'ArcGIS', 'PostGIS', 'GeoServer'],
                'Remote sensing'    => ['Google Earth Engine', 'Landsat', 'Sentinel', 'SNAP'],
                'Spatial analysis'  => ['Raster & vector processing', 'Network analysis', 'Suitability modelling'],
                'Cartography'       => ['Thematic map design', 'Typography', 'Inkscape', 'Illustrator'],
                'Web GIS'           => ['Leaflet', 'GeoJSON', 'OpenStreetMap'],
                'Programming'       => ['Python (rasterio, geopandas)', 'R (terra, sf)', 'SQL', 'JavaScript'],
            ],
            'experience'   => [                                                       // PLACEHOLDER
                // Do NOT invent employers or roles you did not have.
                // Leave this array empty until you have real experience to add.
                // Example shape:
                // ['role' => 'GIS Intern', 'org' => 'Example Org', 'year' => '2024',
                //  'summary' => 'One or two sentences on what you actually did.'],
            ],
            'links'        => [
                'linkedin' => null,  // PLACEHOLDER — e.g. 'https://www.linkedin.com/in/your-handle'
                'github'   => null,  // PLACEHOLDER — e.g. 'https://github.com/your-handle'
                'email'    => null,  // PLACEHOLDER — e.g. 'you@example.com'  (used as mailto:)
                'cv'       => null,  // PLACEHOLDER — e.g. '/uploads/pdfs/cv.pdf'
            ],
        ];

        ob_start();
        require dirname(__DIR__) . '/Views/public/about.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/public.php';
    }
}