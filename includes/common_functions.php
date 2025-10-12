<?php
function renderCorrect($correct, $options) {
    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    $result = [];
    if (is_array($correct)) {
        foreach ($correct as $i) {
            if (isset($options[$i])) {
                $result[] = $letters[$i] . '. ' . htmlspecialchars($options[$i]);
            }
        }
    } else {
        $i = (int)$correct;
        if (isset($options[$i])) {
            $result[] = $letters[$i] . '. ' . htmlspecialchars($options[$i]);
        }
    }
    return implode(', ', $result);
}
?>
