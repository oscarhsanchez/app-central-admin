<?php

namespace AppBundle\Controller;

use AppBundle\Form\UbicacionImagenType;
use AppBundle\Form\UbicacionType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Vallas\ModelBundle\Entity\Ubicacion;

/**
 * Class UbicacionDisponibilidadController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * Ubicacion controller.
 *
 * @Route("/{_locale}/location/availability", defaults={"_locale"="en"})
 */
class UbicacionDisponibilidadController extends VallasAdminController {

    public function indexAction($month, $medio=null){

        $arrMonth = explode('-',$month);
        $firstDateMonth = new \DateTime($arrMonth[1].'-'.$arrMonth[0].'-01');

        $actualDateAux = clone $firstDateMonth;
        $actualDateAux2 = clone $firstDateMonth;

        $prevMonth = $actualDateAux->sub(new \DateInterval('P1M'));
        $nextMonth = $actualDateAux2->add(new \DateInterval('P1M'));

        $arrMonths = array("", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

        return $this->render('AppBundle:screens/ubicacion/disponibilidad/calendar:index.html.twig', array(
            'month' => $month,
            'monthString'=>$arrMonths[intval($firstDateMonth->format('n'))].' '.$arrMonth[1],
            'arrMonths'=>$arrMonths,
            'arrDays'=>array(),
            'prevMonth' => $prevMonth->format('m-Y'),
            'nextMonth' => $nextMonth->format('m-Y'),
            'medio' => $medio));
    }

    /**
     * @Route("/{pkUbicacion}/async/availability", name="ubicacion_disponibilidad_list")
     *
     * @Method("GET")
     */
    public function monthAction($pkUbicacion)
    {

        $ubicacion = $this->getDoctrine()->getManager()->getRepository('VallasModelBundle:Ubicacion')->find($pkUbicacion);

        $arrMonths = array("", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
        $month = $this->getVar('month');
        $pkMedio = $this->getVar('medio');
        $arrMonth = explode('-',$month);
        $date = new \DateTime($arrMonth[1].'-'.$arrMonth[0].'-01');

        $actualDateAux = clone $date;
        $actualDateAux2 = clone $date;

        $prevMonth = $actualDateAux->sub(new \DateInterval('P1M'));
        $nextMonth = $actualDateAux2->add(new \DateInterval('P1M'));

        $dt_start = clone $date;
        $dt_end = clone $nextMonth;
        $dt_end_aux = clone $dt_end;
        $dt_end_aux->sub(new \DateInterval('P1D'));

        $arrDays = array();

        //dia de la semana (1-7) del primer día del mes dado. Ejemplo: Viernes -> 5
        $diaSemanaPrimerDia = $dt_start->format('w');
        if ($diaSemanaPrimerDia == 0) $diaSemanaPrimerDia = 7;
        //dia de la semana (1-7) del primer día del mes dado. Ejemplo: Viernes -> 5
        $diaSemanaUltimoDia = $dt_end_aux->format('w');
        if ($diaSemanaUltimoDia == 0) $diaSemanaUltimoDia = 7;

        $dt_prev = clone $dt_start;
        $dt_prev->sub(new \DateInterval('P'.($diaSemanaPrimerDia-1).'D'));
        for($i=1; $i<$diaSemanaPrimerDia;$i++){
            $arrDays[$dt_prev->format('Y-m-d')] = array('bgColor' => null, 'day' => $dt_prev->format('d'), 'isActualMonth' => false);
            $dt_prev->add(new \DateInterval('P1D'));
        }

        $em = $this->getDoctrine()->getManager();


        $filtroFechas = '((propuesta.fecha_inicio <= :intervalo_inicial AND (propuesta.fecha_fin >= :intervalo_final OR propuesta.fecha_fin BETWEEN :intervalo_inicial AND :intervalo_final)) OR (propuesta.fecha_inicio BETWEEN :intervalo_inicial AND :intervalo_final AND (propuesta.fecha_fin >= :intervalo_final OR propuesta.fecha_fin BETWEEN :intervalo_inicial AND :intervalo_final)))';
        $qb = $em->getRepository('VallasModelBundle:Medio')->createQueryBuilder('medio');
        $qb
            ->addSelect('pdo')
            ->addSelect('pd')
            ->addSelect('propuesta')
            ->leftJoin('medio.ubicacion', 'ubi')
            ->leftJoin('medio.propuesta_detalle_outdoors', 'pdo', 'WITH', 'pdo.estado > 0')
            ->leftJoin('pdo.propuestaDetalle', 'pd', 'WITH', 'pd.estado > 0 AND pd.ubicacion = :ubi')
            ->leftJoin('pd.propuesta', 'propuesta', 'WITH', 'propuesta.estado > 0 AND '.$filtroFechas)
            ->andWhere('medio.estado > 0')
            ->andWhere('medio.id_cara = 1')
            ->andWhere('medio.ubicacion = :ubi')
            ->setParameter('ubi', $ubicacion)
            ->setParameter('intervalo_inicial', $dt_start->format('Y-m-d'))
            ->setParameter('intervalo_final', $dt_end_aux->format('Y-m-d'));

        /*
        $qb = $em->getRepository('VallasModelBundle:PropuestaDetalleOutdoor')->createQueryBuilder('pdo');
        $qb->leftJoin('pdo.propuestaDetalle', 'pd', 'WITH', 'pd.estado > 0')
            ->leftJoin('pdo.medio', 'medio', 'WITH', 'medio.estado > 0')
            ->leftJoin('pd.propuesta', 'propuesta', 'WITH', 'propuesta.estado > 0')
            ->andWhere('pdo.estado > 0')
            ->andWhere('pd.ubicacion = :ubi')
            ->andWhere('medio.ubicacion = :ubi')
            ->andWhere('medio.id_cara = 1')
            ->andWhere('((propuesta.fecha_inicio <= :intervalo_inicial AND (propuesta.fecha_fin >= :intervalo_final OR propuesta.fecha_fin BETWEEN :intervalo_inicial AND :intervalo_final)) OR (propuesta.fecha_inicio BETWEEN :intervalo_inicial AND :intervalo_final AND (propuesta.fecha_fin >= :intervalo_final OR propuesta.fecha_fin BETWEEN :intervalo_inicial AND :intervalo_final)))')
            ->setParameter('ubi', $ubicacion)
            ->setParameter('intervalo_inicial', $dt_start->format('Y-m-d'))
            ->setParameter('intervalo_final', $dt_end_aux->format('Y-m-d'));
        */

        if ($pkMedio){
            $qb->andWhere('medio.pk_medio = :pkMedio')->setParameter('pkMedio', $pkMedio);
        }

        $medios = $qb->getQuery()->getResult();
        //var_dump($propuestasDO);
        for($i=clone $dt_start; $i<$dt_end; $i->add(new \DateInterval('P1D'))){
            $count = 0;
            $slots = 0;

            if ($i->format('Y-m-d') == '2016-02-26'){
                $a="";
            }

            $cliente = null;
            foreach($medios as $medio){

                $slots += $medio->getSlots();

                foreach($medio->getPropuestaDetalleOutdoors() as $pdo) {
                    $p = $pdo->getPropuestaDetalle() ? $pdo->getPropuestaDetalle()->getPropuesta() : null;

                    if (!$p) continue;

                    $fechaInicio = $p->getFechaInicio();
                    $fechaFin = $p->getFechaFin();

                    if ($i >= $fechaInicio && $i <= $fechaFin) {
                        $count++;
                        if ($pkMedio) $cliente = $p->getCliente();
                    }
                }
            }

            $type = null;
            $bgColor = null;
            if ($pkMedio){
                $type = 1;
                $bgColor = '#ffffff';
                if ($count > 0){ $bgColor = '#f2dede'; }
            }else{
                if ($count >= $slots){ $type = 3; $bgColor = '#f2dede'; }
                if ($count > 0 && $count < $slots){ $type = 2; $bgColor = '#f8ac59'; }
                if ($count == 0 || !$type){ $type = 1; $bgColor = '#ffffff'; }
            }

            $arrParamsDay = array('type' => $type, 'bgColor' => $bgColor, 'day' => $i->format('d'), 'isActualMonth' => true);
            if ($pkMedio){
                $arrParamsDay['cliente'] = $cliente ? $cliente->getRazonSocial() : null;
            }
            $arrDays[$i->format('Y-m-d')] = $arrParamsDay;
        }

        //var_dump($arrDays);exit;

        //Añadimos dias para completar la semana
        for($diaSemana=$diaSemanaUltimoDia; $diaSemana<7;$diaSemana++){
            $arrDays[$i->format('Y-m-d')] = array('bgColor' => null, 'day' => $i->format('d'), 'isActualMonth' => false);
            $i->add(new \DateInterval('P1D'));
        }

        $template = 'AppBundle:screens/ubicacion/disponibilidad/calendar:index.html.twig';
        if ($pkMedio){
            $template = 'AppBundle:screens/ubicacion/disponibilidad/calendar:medio_calendar.html.twig';
        }

        return $this->render($template, array(
            'monthString'=>$arrMonths[intval($dt_start->format('n'))].' '.$arrMonth[1],
            'arrMonths'=>$arrMonths,
            'arrDays' => $arrDays,
            'month' => $month,
            'prevMonth' => $prevMonth->format('m-Y'),
            'nextMonth' => $nextMonth->format('m-Y'),
            'ubicacion' => $ubicacion,
            'medio' => $pkMedio
        ));
        //return new JsonResponse($arrDays);

        /*
SELECT *
FROM propuestas_detalle_outdoor
JOIN propuestas_detalle ON fk_propuesta_detalle = pk_propuesta_detalle AND propuestas_detalle.estado > 0
JOIN propuestas ON fk_propuesta = pk_propuesta AND propuestas.estado > 0
JOIN medios ON fk_medio = pk_medio AND medios.estado > 0
WHERE propuestas_detalle_outdoor.estado > 0 AND propuestas_detalle.fk_ubicacion = 'ubicacion28' AND id_cara = 1
AND (
(fecha_inicio <= '2015-07-01' AND (fecha_fin >= '2015-07-31' OR fecha_fin BETWEEN '2015-07-01' AND '2015-07-31'))
OR
(fecha_inicio BETWEEN '2015-07-01' AND '2015-07-31' AND (fecha_fin >= '2015-07-31' OR fecha_fin BETWEEN '2015-07-01' AND '2015-07-31'))
)
         */

        //$response = $this->getDatatableManager()->getResults();

        //return new JsonResponse($response);

    }

    /**
     * @Route("/{pkUbicacion}/availability/{day}", name="ubicacion_disponibilidad_list_day")
     *
     * @Method("GET")
     */
    public function dayAction($pkUbicacion, $day)
    {
        $ubicacion = $this->getDoctrine()->getManager()->getRepository('VallasModelBundle:Ubicacion')->find($pkUbicacion);
        return $this->render('AppBundle:screens/ubicacion/disponibilidad/calendar:day.html.twig', array('ubicacion' => $ubicacion, 'day' => $day));

    }

    /**
     * @Route("/{pkUbicacion}/async/availability/{day}/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="ubicacion_disponibilidad_list_day_json")
     * @return JsonResponse
     * @Method("GET")
     */
    public function dayJsonAction($pkUbicacion, $day)
    {

        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('VallasModelBundle:Medio')->createQueryBuilder('medio');
        $qb
            ->addSelect('pdo')
            ->addSelect('pd')
            ->addSelect('propuesta')
            ->addSelect('cliente')
            ->leftJoin('medio.ubicacion', 'ubi')
            ->leftJoin('medio.propuesta_detalle_outdoors', 'pdo', 'WITH', 'pdo.estado > 0')
            ->leftJoin('pdo.propuestaDetalle', 'pd', 'WITH', 'pd.estado > 0 AND pd.ubicacion = :ubi')
            ->leftJoin('pd.propuesta', 'propuesta', 'WITH', 'propuesta.estado > 0 AND :fecha BETWEEN propuesta.fecha_inicio AND propuesta.fecha_fin')
            ->leftJoin('propuesta.cliente', 'cliente')
            ->andWhere('medio.estado > 0')
            ->andWhere('medio.ubicacion = :ubi')
            ->andWhere('medio.id_cara = 1')
            ->setParameter('fecha', $day)
            ->setParameter('ubi', $pkUbicacion);

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'pk_medio', 'posicion', 'tipo_medio', 'slots', 'propuesta_detalle_outdoors'));
        $jsonList->setSearchFields(array('tipo_medio', 'slots'));
        $jsonList->setRepository($em->getRepository('VallasModelBundle:Medio'));
        $jsonList->setQueryBuilder($qb);

        //echo $qb->getQuery()->getSQL();exit;

        $response = $jsonList->getResults();

        foreach($response['aaData'] as $key=>$row){
            $reg = $response['aaData'][$key];

            $propuestaDetalleOutdoor = count($reg['propuesta_detalle_outdoors'])>0 ? $reg['propuesta_detalle_outdoors'][0] : null;
            $cliente = $propuestaDetalleOutdoor && $propuestaDetalleOutdoor->getPropuestaDetalle() && $propuestaDetalleOutdoor->getPropuestaDetalle()->getPropuesta() ? $propuestaDetalleOutdoor->getPropuestaDetalle()->getPropuesta()->getCliente() : null;

            $response['aaData'][$key]['estado_code'] = count($reg['propuesta_detalle_outdoors']) < $reg['slots'] ? '0' : '1';
            $response['aaData'][$key]['estado'] = count($reg['propuesta_detalle_outdoors']) < $reg['slots'] ? 'Libre' : 'Ocupada';
            $response['aaData'][$key]['cliente'] = $cliente ? $cliente->getRazonSocial() : null;

            unset($response['aaData'][$key]['propuesta_detalle_outdoors']);
        }


        return new JsonResponse($response);
    }

}