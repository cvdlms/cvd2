<?php
function renderCorrect($correct, $options) {
    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    $correctIndices = is_array($correct) ? $correct : [$correct];
    $correctLetters = array_map(function($idx) use ($letters) {
        return $letters[$idx] ?? '?';
    }, $correctIndices);
    return implode(', ', $correctLetters);
}
?>
