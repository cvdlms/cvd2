<?php
/**
 * Presentation Storage Helper
 * Manages individual presentation files and index
 */

class PresentationStorage {
    private $indexFile;
    private $presentationsDir;
    
    public function __construct() {
        $this->indexFile = __DIR__ . '/../data/presentations_index.json';
        $this->presentationsDir = __DIR__ . '/../uploads/presentations/';
        
        // Ensure directories exist
        if (!is_dir($this->presentationsDir)) {
            mkdir($this->presentationsDir, 0755, true);
        }
    }
    
    /**
     * Get all presentations metadata (from index)
     */
    public function getAllMetadata() {
        if (!file_exists($this->indexFile)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($this->indexFile), true);
        return is_array($data) ? $data : [];
    }
    
    /**
     * Get presentations for specific teacher
     */
    public function getByTeacher($username) {
        $all = $this->getAllMetadata();
        return array_filter($all, function($p) use ($username) {
            return $p['teacher_username'] === $username;
        });
    }
    
    /**
     * Get full presentation data by ID
     */
    public function getById($id) {
        $dataFile = $this->presentationsDir . $id . '.json';
        
        if (!file_exists($dataFile)) {
            return null;
        }
        
        return json_decode(file_get_contents($dataFile), true);
    }
    
    /**
     * Save presentation (creates/updates both data file and index)
     */
    public function save($presentation) {
        $id = $presentation['id'];
        $dataFile = $this->presentationsDir . $id . '.json';
        
        // Save full data to individual file
        file_put_contents($dataFile, json_encode($presentation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Update index
        $this->updateIndex($presentation);
        
        return true;
    }
    
    /**
     * Update index with presentation metadata
     */
    private function updateIndex($presentation) {
        $index = $this->getAllMetadata();
        
        // Create metadata entry
        $metadata = [
            'id' => $presentation['id'],
            'title' => $presentation['title'] ?? 'Untitled',
            'teacher_username' => $presentation['teacher_username'],
            'subject_id' => $presentation['subject_id'] ?? '',
            'grade' => $presentation['grade'] ?? '',
            'slide_count' => count($presentation['slides'] ?? []),
            'created_at' => $presentation['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'source' => $presentation['source'] ?? 'template',
            'thumbnail' => $presentation['thumbnail'] ?? '',
            'data_file' => 'uploads/presentations/' . $presentation['id'] . '.json'
        ];
        
        // Find and update or append
        $found = false;
        foreach ($index as &$item) {
            if ($item['id'] === $presentation['id']) {
                $item = $metadata;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $index[] = $metadata;
        }
        
        // Save index with file locking
        $fp = fopen($this->indexFile, 'w');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
    
    /**
     * Delete presentation
     */
    public function delete($id) {
        // Delete data file
        $dataFile = $this->presentationsDir . $id . '.json';
        if (file_exists($dataFile)) {
            unlink($dataFile);
        }
        
        // Remove from index
        $index = $this->getAllMetadata();
        $index = array_filter($index, function($p) use ($id) {
            return $p['id'] !== $id;
        });
        
        file_put_contents($this->indexFile, json_encode(array_values($index), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return true;
    }
    
    /**
     * Check if using old storage system
     */
    public function isLegacySystem() {
        $oldFile = __DIR__ . '/../data/presentations.json';
        return file_exists($oldFile) && !file_exists($this->indexFile);
    }
    
    /**
     * Get presentations from legacy system (old presentations.json)
     */
    public function getLegacyPresentations() {
        $oldFile = __DIR__ . '/../data/presentations.json';
        if (!file_exists($oldFile)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($oldFile), true);
        return is_array($data) ? $data : [];
    }
}
