<?php
namespace rtens\dox;

class Configuration {

    public static $CLASS = __CLASS__;

    /** @var array|ProjectConfiguration[] */
    private $projects = array();

    private $root;

    function __construct($root) {
        $this->root = $root;
    }

    public function addProject($projectName) {
        $this->projects[$projectName] = new ProjectConfiguration($this, $projectName);
        return $this->projects[$projectName];
    }

    public function getProject($project) {
        return $this->projects[$project];
    }

    public function getProjects() {
        return array_keys($this->projects);
    }

    public function inUserFolder($path) {
        return $this->getUserFolder() . DIRECTORY_SEPARATOR . $path;
    }

    public function inProjectsFolder($path) {
        return $this->inUserFolder('projects' . DIRECTORY_SEPARATOR . $path);
    }

    protected function getUserFolder() {
        return $this->root . DIRECTORY_SEPARATOR . 'user';
    }
}