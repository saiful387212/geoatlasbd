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
    'name'         => 'Saiful Islam Irvan',
    'role'         => 'Geography & Environment Student · GIS Enthusiast',
    'location'     => 'University of Dhaka, Bangladesh',
    'bio_short'    => 'A motivated Geography & Environment student at the University of Dhaka, passionate about GIS, remote sensing, and sustainable development. Dedicated to using geospatial technology for community welfare and environmental research.',
    'bio_long'     => [
        'I am currently pursuing my BSc (Hons) in Geography & Environment at the University of Dhaka. My academic journey is driven by a deep curiosity about how spatial data and geographic information systems (GIS) can be used to solve real-world environmental and social challenges.',
        'Beyond academics, I am deeply involved in leadership and community service. As the founder of two organizations and an executive member of several university societies, I believe in creating positive social impact. My goal is to pursue advanced studies abroad and contribute to global environmental research and sustainable development.'
    ],
    'education'    => [
        ['degree' => 'BSc (Hons) in Geography & Environment', 'institution' => 'University of Dhaka', 'year' => 'Ongoing (2nd Year)'],
        ['degree' => 'HSC', 'institution' => 'Comilla Victoria Govt. College (GPA: 5.00)', 'year' => 'Completed'],
        ['degree' => 'SSC', 'institution' => 'Sher-e-Bangla High School, Comilla Board (GPA: 5.00)', 'year' => 'Completed'],
    ],
    'interests'    => [
        'Geographic Information Systems (GIS)',
        'Remote Sensing & Spatial Analysis',
        'Cartography & Map Design',
        'Climate Hazard Management',
        'Environmental Sustainability',
        'Community Welfare & Leadership',
    ],
    'skills'       => [
        'GIS & Mapping'        => ['ArcGIS', 'Spatial Analysis', 'Cartography', 'Surveying (Total Station, Leveling)'],
        'Programming & AI'     => ['Python (Basic Data Analysis & Visualization)', 'AI Web Design', 'Prompt Engineering'],
        'Languages'            => ['English (Professional)', 'German (A1 Level)'],
        'Office & Productivity'=> ['MS Word', 'MS Excel', 'MS PowerPoint'],
        'Leadership'           => ['Team Management', 'Organizational Leadership', 'Event Management'],
    ],
    'experience'   => [
        ['role' => 'Founder', 'org' => 'Human Welfare Youth & Expat Organization', 'year' => 'Present', 'summary' => 'Leading community welfare initiatives for youth and expatriates.'],
        ['role' => 'Founder', 'org' => 'OpenShelf: DU Readers Circle', 'year' => 'Present', 'summary' => 'Fostering a culture of reading and critical thinking at the University of Dhaka.'],
        ['role' => 'Treasurer', 'org' => 'Darul Ittihad Foundation', 'year' => 'Present', 'summary' => 'Managing financial operations and ensuring transparent fund utilization.'],
        ['role' => 'Executive Member', 'org' => 'Dhaka University Environment Society', 'year' => 'Present', 'summary' => 'Organizing environmental awareness campaigns and sustainability programs.'],
        ['role' => 'Executive Member', 'org' => 'Badhon (Blood Donors Organization)', 'year' => 'Present', 'summary' => 'Volunteering for blood donation drives and health awareness.'],
    ],
    'achievements' => [
        'Best Upazilla Student Award - SSC (Recognition for outstanding academic performance).',
        'Completed an 8-Day Training on Water Level Data Collection, Processing & Analysis for Climate Hazard Management in the Bangladesh Delta (University of Dhaka).',
        'Completed "Be a Lifesaver: First Aid Bootcamp".',
    ],
    'links'        => [
        'linkedin' => null, // উদাহরণ: 'https://www.linkedin.com/in/saiful-islam-irvan-a55621363?utm_source=share_via&utm_content=profile&utm_medium=member_android'
        'github'   => null, // উদাহরণ: 'https://github.com/username'
        'email'    => null, // উদাহরণ: 'saif387212@gmail.com'
        'cv'       => url('/assets/files/Saiful_Islam_Irvan_CV.pdf'),
    ],
];

        ob_start();
        require dirname(__DIR__) . '/Views/public/about.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/public.php';
    }
}