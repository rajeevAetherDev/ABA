<?php

class UsermainmenuController extends AppController {
    public function dashboard() {
        // Load necessary data from models
        $this->set('title', 'Dashboard');
    }

    public function profile() {
        // Load necessary data from models
        $this->set('title', 'Profile');
    }

    // Other actions...
}
?>