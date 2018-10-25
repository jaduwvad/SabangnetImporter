<?php

namespace SabangnetImporter;

use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Components\Plugin;

class SabangnetImporter extends Plugin {
    /**
     * {@inheritdoc}
     */

    public static function getSubscribedEvents(){
        return [
            'Enlight_Controller_Action_PreDispatch' => 'addTemplateDir'
        ];
    }

    public function addTemplateDir(\Enlight_Controller_ActionEventArgs $args){
        $request = $args->getRequest();
        $view = $args->getSubject()->View();

        $view->addTemplateDir( $this->getPath() . '/Resources/views');

        if($request->getActionName() === 'index') {
            $view->extendsTemplate('backend/s_importer/app.js');
        }
        if($request->getActionName() === 'load') {
            $view->extendsTemplate('backend/s_importer/view/list/window.js');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function install(Plugin\Context\InstallContext $context)
    {
        $this->updateSchema();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(Plugin\Context\UninstallContext $context)
    {
        $tool = new SchemaTool($this->container->get('models'));
        $classes = $this->getModelMetaData();
        $tool->dropSchema($classes);
    }

    private function updateSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $classes = $this->getModelMetaData();

        try {
            $tool->dropSchema($classes);
        } catch (\Exception $e) {
        }

        $tool->createSchema($classes);
    }

    /**
     * @return array
     */
    private function getModelMetaData()
    {
        return [$this->container->get('models')->getClassMetadata(Models\Order::class)];
    }
}
