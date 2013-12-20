<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\Constraints as Assert;

class FtpExportFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_activeFTP', 'checkbox', array(
            'label'        => _('Enable FTP export'),
            'data'         => false,
            'help_message' => _('Available in multi-export tab'),
        ));
        $builder->add('GV_ftp_for_user', 'checkbox', array(
            'label'        => _('Enable FTP for users'),
            'data'         => false,
            'help_message' => _('By default it is available for admins'),
        ));
    }

    public function getName()
    {
        return null;
    }
}
