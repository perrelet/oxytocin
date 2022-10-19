<?php

namespace Oxytocin;

class Updater extends \Digitalis\Updater {

    protected $remote_json = 'https://digitalis.ca/plugins/update/oxytocin/info';
	protected $plugin_slug = OXYTOCIN_SLUG;
	protected $plugin_base = OXYTOCIN_BASE;
	protected $version = OXYTOCIN_VERSION;

}