<?php
class JsonDB {
    protected $filename;

    public function __construct($filename) {
        $this->filename = $filename;
    }

    public function saveData($data) {
        $jsonData = json_encode($data);
        file_put_contents($this->filename, $jsonData);
    }

    public function loadData() {
        $jsonData = file_get_contents($this->filename);
        return json_decode($jsonData, true);
    }
}
