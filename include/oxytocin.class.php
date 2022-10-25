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

        include OXYTOCIN_PATH . 'include/utils/utility.abstract.php';
        include OXYTOCIN_PATH . 'include/utils/genealogist.utility.php';

        include OXYTOCIN_PATH . 'include/models/model.abstract.php';
        include OXYTOCIN_PATH . 'include/models/chart.model.php';
        include OXYTOCIN_PATH . 'include/models/tree.model.php';

        $this->load_integrations(OXYTOCIN_PATH . 'include/integrations');

    }

    protected function load_admin () {

        include OXYTOCIN_PATH . 'include/admin/updater.php';
        $updater = new Updater();

        add_action('admin_enqueue_scripts', function () {

            wp_enqueue_style('oxytocin-admin', OXYTOCIN_URI . 'assets/css/oxytocin.admin.css', [], OXYTOCIN_VERSION);
            wp_enqueue_script('oxytocin-admin', OXYTOCIN_URI . 'assets/js/oxytocin.admin.js', [], OXYTOCIN_VERSION);

        });

    }

    

}