<?php

namespace AppBundle\Filter;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Doctrine\Common\Annotations\Reader;

class Configurator
{
    protected $em;
    /**
     * Annotation Reader.
     *
     * @var Reader
     */
    protected $reader;
    protected $session;
    protected $service_container;

    public function __construct(ContainerInterface $service_container, EntityManager $em, Reader $reader, Session $session)
    {
        $this->em              = $em;
        $this->reader          = $reader;
        $this->session         = $session;
        $this->service_container = $service_container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {

        $request   = $event->getRequest();
        $attributes = $request->attributes->all();
        $controller = $attributes['_controller'];

        $arrControllerData = explode('::', $controller);

        $boolFiltering = true;

        $vallas_country = $this->session->get('vallas_country');
        $vallas_country_id = $vallas_country ? $vallas_country['code'] : null;

        $filters = $this->em->getFilters();
        $queryBuilderParameters = $this->service_container->getParameter('query_filters_entities');

        if (array_key_exists('country_filter',$queryBuilderParameters)){

            if ($filters->isEnabled('country_filter')){

                $filter = $filters->getFilter('country_filter');
                $filter->reset();

                $boolFiltering = true;

                if (!$vallas_country_id) $boolFiltering = false;

                if ($boolFiltering){
                    $filter->setParameter('id', $vallas_country_id);
                    $filter->setFilterParameters($queryBuilderParameters['country_filter']);
                    $filter->setAnnotationReader($this->reader);
                    $filter->setEntityManager($this->em);
                }

            }

        }

    }
}
