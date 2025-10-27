<?php
function renderCorrect($correct, $options) {
    if (is_array($correct)) {
        $letters = [];
        foreach ($correct as $idx) {
            $letters[] = chr(65 + $idx);
        }
        return implode(', ', $letters);
    } else {
        return chr(65 + $correct);
    }
}
?>
