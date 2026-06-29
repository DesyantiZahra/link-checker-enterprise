<?php

function getScanStatus($score, $malicious, $suspicious) {
    if ($score > 90 && $malicious === 0 && $suspicious === 0) {
        return 'safe';
    }
    if ($score >= 50 && $score <= 70) {
        return 'suspicious';
    }
    if ($score < 40) {
        return 'malicious';
    }
    return ($malicious > 0 || $suspicious > 0) ? 'suspicious' : 'safe';
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
