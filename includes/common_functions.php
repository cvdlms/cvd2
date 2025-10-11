<?php
function renderCorrect($correct, $options) {
    if (is_array($correct)) {
        return implode(', ', array_map(function($c) {
            if (is_numeric($c)) {
                $letters = ['A', 'B', 'C', 'D'];
                return $letters[$c] ?? $c;
            }
            return $c;
        }, $correct));
    }
    if (is_numeric($correct)) {
        $letters = ['A', 'B', 'C', 'D'];
        return $letters[$correct] ?? $correct;
    }
    return $correct;
}
?>
