<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2019 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_410alpha20a implements patchInterface
{
    /** @var string */
    private $release = '4.1.0-alpha.20a';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * Returns the release version.
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        // fix embed-bundle keys
        if ($app['conf']->has(['embed_bundle', 'video', 'available-speeds'])) {
            $availableSpeed = $app['conf']->get(['embed_bundle', 'video', 'available-speeds']);
            $app['conf']->remove(['embed_bundle', 'video', 'available-speeds']);
            $app['conf']->set(['embed_bundle', 'video', 'available_speeds'], $availableSpeed);
        }

        if ($app['conf']->has(['embed_bundle', 'audio', 'available-speeds'])) {
            $availableSpeed = $app['conf']->get(['embed_bundle', 'audio', 'available-speeds']);
            $app['conf']->remove(['embed_bundle', 'audio', 'available-speeds']);
            $app['conf']->set(['embed_bundle', 'audio', 'available_speeds'], $availableSpeed);
        }

        if ($app['conf']->has(['embed_bundle', 'document', 'enable-pdfjs'])) {
            $enablePdfjs = $app['conf']->get(['embed_bundle', 'document', 'enable-pdfjs']);
            $app['conf']->remove(['embed_bundle', 'document', 'enable-pdfjs']);
            $app['conf']->set(['embed_bundle', 'document', 'enable_pdfjs'], $enablePdfjs);
        }

        //  geoloc section change replace 'name' to 'map-provider'
        if ($app['conf']->has(['geocoding-providers', 0, 'name'])) {
            $geocodingName = $app['conf']->get(['geocoding-providers', 0, 'name']);
            $app['conf']->remove(['geocoding-providers', 0, 'name']);
            $app['conf']->set(['geocoding-providers', 0, 'map-provider'], $geocodingName);
        }

        // remove registry classic section
        if ($app['conf']->has(['registry', 'classic'])) {
            $app['conf']->remove(['registry', 'classic']);
        }

        //  insert RGPD bloc if not exist
        if (!$app['conf']->has(['user_account', 'deleting_policies', 'email_confirmation'])) {
            $app['conf']->set(['user_account', 'deleting_policies', 'email_confirmation'], true);
        }

        return true;
    }
}
