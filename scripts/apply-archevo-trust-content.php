<?php

declare(strict_types=1);

/**
 * Team, testimonials (project client names), and modest trust highlights.
 * Run: php scripts/apply-archevo-trust-content.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = Database::connection($GLOBALS['configDb']);

setting_set($pdo, 'contact_page_lead', 'Reach Jay Rathod and the studio team for new projects, plan approvals, interiors, or turnkey delivery.');
setting_set($pdo, 'home_awards_eyebrow', 'Studio');
setting_set($pdo, 'home_awards_title', 'Why clients work with us');
setting_set($pdo, 'home_testimonials_title', 'What clients say');

$pdo->exec('DELETE FROM team_members');
$team = [
    ['Jay Rathod', 'Director', 'Leads project direction, client relationships, and overall delivery for Archevo Infra Edge across Gujarat.', 'JR', 1],
    ['Kishan Tank', 'Director', 'Oversees design, interiors, and site coordination — from drawings and 3D through execution.', 'KT', 2],
    ['Jignesh Sekhva', 'Engineer', 'Technical drawings, structural coordination, and on-site engineering support for civil and approval work.', 'JS', 3],
];
$teamStmt = $pdo->prepare(
    'INSERT INTO team_members (name, role_title, bio, image_path, initials, sort_order, is_active) VALUES (?,?,?,?,?,?,1)'
);
foreach ($team as $row) {
    $teamStmt->execute([$row[0], $row[1], $row[2], '', $row[3], $row[4]]);
}

$pdo->exec('DELETE FROM testimonials');
$testimonials = [
    [
        '“Our living and bedroom layouts were planned practically. The team was on site when it mattered and handover was smooth.”',
        'Hareshbhai',
        'Residence · Rajkot',
    ],
    [
        '“3D views and drawings helped us decide finishes before work started. Plan-related queries were handled without us chasing offices.”',
        'Kantilalbhai',
        'Residence · Rajkot',
    ],
    [
        '“Plot layout and front elevation matched what we discussed. One studio for design and coordination through construction.”',
        'Sanjaysinh Jadeja',
        'Plot & residence · Rajkot',
    ],
    [
        '“Interior detailing and civil work moved in step. We always knew who to call and what was happening next.”',
        'Arvind Parmar',
        'Residence · Rajkot',
    ],
];
$testStmt = $pdo->prepare(
    'INSERT INTO testimonials (quote, author_name, author_role, sort_order, is_active) VALUES (?,?,?,?,1)'
);
foreach ($testimonials as $i => $row) {
    $testStmt->execute([$row[0], $row[1], $row[2], $i + 1]);
}

$pdo->exec('DELETE FROM awards');
$awards = [
    ['fas fa-map-location-dot', 'Rajkot & Saurashtra', 'On-ground experience across Gujarat — not a distant design-only studio.', 1],
    ['fas fa-file-lines', 'Approvals support', 'Drawings and submissions prepared for local plan-sanctioning requirements.', 2],
    ['fas fa-handshake', 'Referral-led work', 'A large share of new projects come from past clients and word of mouth.', 3],
    ['fas fa-house-chimney', 'Turnkey under one roof', 'Design, civil, and interiors coordinated by one accountable team.', 4],
];
$awardStmt = $pdo->prepare(
    'INSERT INTO awards (icon_class, title, subtitle, sort_order, is_active) VALUES (?,?,?,?,1)'
);
foreach ($awards as $row) {
    $awardStmt->execute($row);
}

content_sync_site_json($pdo);

echo "Team, testimonials, awards, and contact lead updated.\n";
