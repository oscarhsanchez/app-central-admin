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
 * @Route("/{_locale}/location/availability", defaults={"_locale"="es"})
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


        $intervalo_inicial = "'".$dt_start->format('Y-m-d')."'";
        $intervalo_final = "'".$dt_end_aux->format('Y-m-d')."'";

        $filtroFechas = "((reserva.fecha_inicio <= $intervalo_inicial AND (reserva.fecha_fin >= $intervalo_final OR reserva.fecha_fin BETWEEN $intervalo_inicial AND $intervalo_final)) OR (reserva.fecha_inicio BETWEEN $intervalo_inicial AND $intervalo_final AND (reserva.fecha_fin >= $intervalo_final OR reserva.fecha_fin BETWEEN $intervalo_inicial AND $intervalo_final)))";

        $qb = $em->getRepository('VallasModelBundle:ReservaMedio')->createQueryBuilder('reserva');
        $qb
            ->addSelect('pd')
            ->addSelect('pdo')
            ->addSelect('propuesta')
            ->leftJoin('reserva.medio', 'medio')
            ->leftJoin('reserva.propuestaDetalle', 'pd', 'WITH', 'pd.estado > 0')
            ->leftJoin('pd.propuesta', 'propuesta', 'WITH', 'propuesta.estado > 0')
            ->leftJoin('reserva.propuestaDetalleOutdoor', 'pdo', 'WITH', 'pdo.estado > 0')
            ->andWhere('reserva.estado > 0')
            ->andWhere('medio.estado > 0')
            ->andWhere('medio.id_cara > 0')
            ->andWhere("medio.ubicacion = '$pkUbicacion'")
            ->andWhere($filtroFechas);

        if ($pkMedio){
            $qb->andWhere("reserva.medio = '$pkMedio'");
        }

        $reservas = $qb->getQuery()->getResult();

        for($i=clone $dt_start; $i<$dt_end; $i->add(new \DateInterval('P1D'))){
            $count = 0;
            $slots = 0;

            $cliente = null;
            foreach($reservas as $reserva){

                $medio = $reserva->getMedio();
                $slots += $medio->getSlots();

                foreach($reserva->getPropuestaDetalleOutdoors() as $pdo) {
                    $p = $reserva->getPropuestaDetalle() ? $reserva->getPropuestaDetalle()->getPropuesta() : null;

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

        $repository = $em->getRepository('VallasModelBundle:ReservaMedio');
        $qb = $repository->createQueryBuilder('reserva');
        $qb
            ->addSelect('pd')
            ->addSelect('pdo')
            ->addSelect('propuesta')
            ->addSelect('cliente')
            ->leftJoin('reserva.medio', 'medio')
            ->leftJoin('reserva.propuestaDetalle', 'pd', 'WITH', 'pd.estado > 0')
            ->leftJoin('pd.propuesta', 'propuesta', 'WITH', 'propuesta.estado > 0')
            ->leftJoin('reserva.propuestaDetalleOutdoor', 'pdo', 'WITH', 'pdo.estado > 0')
            ->andWhere('reserva.estado > 0')
            ->andWhere('medio.estado > 0')
            ->andWhere('medio.id_cara > 0')
            ->andWhere("medio.ubicacion = '$pkUbicacion'")
            ->andWhere(":fecha BETWEEN reserva.fecha_inicio AND reserva.fecha_fin")->setParameter('fecha', $day);


        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'medio__pk_medio', 'medio__posicion', 'medio__tipo_medio', 'medio__slots', 'propuestaDetalle__propuesta__cliente'));
        $jsonList->setSearchFields(array('medio__tipo_medio', 'medio__slots'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        $response = $jsonList->getResults();

        $arrReservas = array();
        foreach($response['aaData'] as $key=>$row){
            $reg = $response['aaData'][$key];

            if (!array_key_exists($reg['medio__pk_medio'], $arrReservas)){
                $arrReservas[$reg['medio__pk_medio']] = 0;
            }
            $arrReservas[$reg['medio__pk_medio']]++;
        }

        foreach($response['aaData'] as $key=>$row){
            $reg = $response['aaData'][$key];

            $cliente = $reg['propuestaDetalle__propuesta__cliente'];

            $response['aaData'][$key]['estado_code'] = $arrReservas[$reg['medio__pk_medio']] < $reg['medio__slots'] ? '0' : '1';
            $response['aaData'][$key]['estado'] = $arrReservas[$reg['medio__pk_medio']] < $reg['medio__slots'] ? 'Libre' : 'Ocupada';
            $response['aaData'][$key]['cliente'] = $cliente ? $cliente->getRazonSocial() : null;

            unset($response['aaData'][$key]['propuestaDetalle__propuesta__cliente']);
        }

        return new JsonResponse($response);
    }

}