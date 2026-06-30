<?php

function calculateSafetyScore($malicious, $suspicious) {
    if ($malicious > 0) {
        return max(0, 65 - ($malicious - 1) * 20 - $suspicious * 5);
    }
    return max(0, 100 - $suspicious * 10);
}

function getScanStatus($score, $malicious, $suspicious) {
    if ($malicious > 0) {
        return 'malicious';
    }
    if ($suspicious > 0) {
        return 'suspicious';
    }
    if ($score >= 70) {
        return 'safe';
    }
    if ($score >= 40) {
        return 'suspicious';
    }
    return 'malicious';
}

function getStatusBadgeClass($status) {
    $classes = [
        'safe' => 'bg-green-500 text-white',
        'suspicious' => 'bg-yellow-500 text-white',
        'malicious' => 'bg-red-500 text-white'
    ];
    return $classes[$status] ?? 'bg-gray-500 text-white';
}

function getStatusLabel($status) {
    $labels = [
        'safe' => '🟢 Aman',
        'suspicious' => '🟡 Mencurigakan',
        'malicious' => '🔴 Berbahaya'
    ];
    return $labels[$status] ?? '-';
}
