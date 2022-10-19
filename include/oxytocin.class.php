<?php

namespace Oxytocin;

use Digitalis\Has_Integrations;

class Oxytocin extends \Digitalis\Singleton {

    use Has_Integrations;

    protected $store;

    public function run () {
        
        do_action('oxytocin');
        
        $this->load();

        if (is_admin()) $this->load_admin();

    }

    protected function load () {

        $this->load_integrations(OXYTOCIN_PATH . 'include/integrations');

        $this->instantiate();

    }

    protected function load_admin () {

        include OXYTOCIN_PATH . 'include/admin/updater.php';
        $updater = new Updater();

    }

    

}