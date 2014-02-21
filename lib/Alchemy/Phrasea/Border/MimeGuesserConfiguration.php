<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use MediaVorus\Utils\AudioMimeTypeGuesser;
use MediaVorus\Utils\PostScriptMimeTypeGuesser;
use MediaVorus\Utils\RawImageMimeTypeGuesser;
use MediaVorus\Utils\VideoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

class MimeGuesserConfiguration
{
    /** @var PropertyAccess  */
    private $conf;
    private $store;

    public function __construct(PropertyAccess $conf, ConfigurationInterface $store)
    {
        $this->conf = $conf;
        $this->store = $store;
    }

    /**
     * Registers mime type guessers given the configuration
     */
    public function register()
    {
        $guesser = MimeTypeGuesser::getInstance();

        $guesser->register(new FileBinaryMimeTypeGuesser());
        $guesser->register(new RawImageMimeTypeGuesser());
        $guesser->register(new PostScriptMimeTypeGuesser());
        $guesser->register(new AudioMimeTypeGuesser());
        $guesser->register(new VideoMimeTypeGuesser());

        if ($this->store->isSetup()) {
            $guesser->register(new CustomExtensionGuesser($this->conf->get(['border-manager', 'extension-mapping'], [])));
        }
    }
}
