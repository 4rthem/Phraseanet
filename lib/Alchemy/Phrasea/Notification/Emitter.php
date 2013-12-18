<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

class Emitter implements EmitterInterface
{
    private $name;
    private $email;

    public function __construct($name, $email)
    {
        if (!\Swift_Validate::email($email)) {
            throw new InvalidArgumentException(sprintf('Invalid e-mail address (%s)', $email));
        }

        $this->name = $name;
        $this->email = $email;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Creates an Emitter given a User
     *
     * @param \User_Adapter $user
     *
     * @return Emitter
     *
     * @throws InvalidArgumentException In case no valid email is found for user
     */
    public static function fromUser(\User_Adapter $user)
    {
        return new static($user->get_display_name(), $user->get_email());
    }
}
