<?php
/**
 * Gender lookup for the student portal. Reads the single source of truth
 * (admin/students.json) and caches it per request so repeated calls do not
 * re-read the file. Used to switch the dashboard theme for female students.
 */
function getStudentGender($studentCode) {
    static $genders = null;
    if ($genders === null) {
        $genders = [];
        $file = __DIR__ . '/../admin/students.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                foreach ($data as $student) {
                    $genders[(string)($student['code'] ?? '')] = $student['gender'] ?? '';
                }
            }
        }
    }
    return $genders[(string)$studentCode] ?? '';
}
