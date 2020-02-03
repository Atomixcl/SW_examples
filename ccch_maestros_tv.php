<?php

    if ( (isset($_GET['tv'])) || (isset($_GET['idtv'])) ) {
        include_once "../ccch_functions.php";
        ccch_maestro_tv_modal($_GET['idtv'],$_GET['tv'],$_GET['codigo']);
        exit;
    }

    function ccch_maestro_tv_modal($idtv,$tv,$codigo='')
    {
        $inside = '';
        $conn=ccch_connect();
        //var_dump($_GET);
        $inside_style = ' style="padding-top:5px;padding-left:0px;padding-right:0px;margin-left:0px;margin-right:0px;"';
        if ($codigo == '')
        {
            $query=ccch_query("SELECT * from web_tv_devices where idtvdev=" . $idtv);
            while ($row = ccch_fetch($query)) {
            $inside .= '<div class="row" ' .  $inside_style . '>
                           <div class="col-sm-2" style="padding-top:2px;">Nombre</div>
                           <div class="col-sm-4"><input class="form-control" style="height:18px;" type="text" name="dev_name" value="' . trim($row['dev_name']) . '"></div>
                           </div>';

            $inside .= '<div class="row" ' .  $inside_style . '>
                           <div class="col-sm-2" style="padding-top:2px;">Mac Address</div>
                           <div class="col-sm-8" style="display: inline-flex;"><input class="form-control" style="width:200px;height:18px;" type="text" name="dev_mac" value="' . trim($row['dev_mac']) . '"></div>
                       </div>';
            $select='<select name="status">';
            if ($row['state'] == 1)
                $select .='<option selected value="1">Habilitado</option><option value="0">Desabilitado</option>';
            else
                $select .='<option value="1">Habilitado</option><option selected value="0">Desabilitado</option>';
            $select .='</select>';

            $inside .= '<div class="row" ' .  $inside_style . '>
                           <div class="col-sm-2" style="padding-top:2px;">Status</div>
                           <div class="col-sm-2">' . $select . '</div>
                        </div>
                        <div class="row" ' .  $inside_style . '>';

            }
        }
        else
        {
            $daysinweek = array(
                                "2"=> "Lu",
                                "3"=> "Ma",
                                "4"=> "Mi",
                                "5"=> "Ju",
                                "6"=> "Vi",
                                "7"=> "Sa",
                                "1"=> "Do"
                            );
            $query=ccch_query("SELECT * from web_tv where idtv=" . $idtv);
            while ($row = ccch_fetch($query)) {
                if ($row['fecha'] == '' )
                    $dt='';
                else
                    $dt=date('d-m-Y',strtotime($row['fecha']));
                if ($row['codigo'] == 2) {
                    $filename='/var/www/html/wordpress/wp-content/uploads/tv/' . $row['idtv'].'.'.$row['html'];
                    if (file_exists($filename)) $code4file=$filename; else $code4file='';
                    $html = $row['html'];
                }
                if ($row['codigo'] == 4) {
                    $get_html = array();
                    $get_html = unserialize($row['html']);
                    if (isset($row['html']))
                    {
                        $acc_area = $get_html[0];
                        $acc_tipo= $get_html[1];
                    } else
                    {
                        $acc_area = '';
                        $acc_tipo='';
                    }
                }

                if ($row['expira'] == '' )
                    $expira='';
                else
                    $expira=date('d-m-Y',strtotime($row['expira']));
                if ($row['inicio'] == '' )
                    $inicio='';
                else
                    $inicio=date('d-m-Y',strtotime($row['inicio']));
                $htmlDisabled='';
                if ($row['codigo'] == 1) $htmlDisabled='pointer-events:none;';
                if (isset($row['orden'])) $orden=$row['orden']; else $orden='999';
                $inside='';
                if ($row['codigo']==3) $fw='<a target="_blank" href="https://fontawesome.com/v4.7.0/icons/">Íconos</a>'; else $fw='';
                $inside .= '<div class="row" ' .  $inside_style . '>
                               <div class="col-sm-2" style="padding-top:2px;">Titulo</div>
                               <div class="col-sm-4"><input class="form-control" style="height:18px;" type="text" name="titulo" value="' . $row['titulo'] . '"></div>
                               </div>';

                if ($row['codigo'] != 2) $inside .= '<div class="row" ' .  $inside_style . '>
                                                       <div class="col-sm-2" style="padding-top:2px;">Ícono</div>
                                                       <div class="col-sm-8" style="display: inline-flex;"><input class="form-control" style="width:200px;height:18px;" type="text" name="icono" value="' . $row['icono'] . '"> ' . $fw . '</div>
                                                   </div>';
                    else {
                            $select='<select name="icono">';
                            if ($row['icono'] == 'Video')
                            {
                                $select .='<option value="Imagen">Imagen</option><option selected value="Video">Video</option>';
                                $selected_video = False;
                            }
                            else
                            {
                                $select .='<option selected value="Imagen">Imagen</option><option value="Video">Video</option>';
                                $selected_video = True;
                            }
                            $select .='</select>';
                            $inside .= '<div class="row" ' .  $inside_style . '>
                                                       <div class="col-sm-2" style="padding-top:2px;">Tipo</div>
                                                       <div class="col-sm-8" style="display: inline-flex;">' . $select . '</div>
                                                   </div>';
                           }
                $fn_short = '../wp-content/uploads/tv/' . $row['idtv'].'.'.$row['html'];
                if ($row['html'] == "m4v") $video_type = "mp4"; else $video_type = $row['html'];
                 if ($selected_video) $iframe = '<!DOCTYPE html><img width=&quot;' . round($row['w']/2,0) . '&quot; height=&quot;' . round($row['h']/2,0) . '&quot; src=&quot;' . $fn_short . '&quot;>' ;
                    else $iframe = '<!DOCTYPE html><video width=&quot;' . round($row['w']/2,0) . '&quot; height=&quot;' . round($row['h']/2,0) . '&quot; autoplay controls muted>
                                      <source src=&quot;' . $fn_short . '&quot; type=&quot;video/' . $video_type . '&quot;>
                                      El browser no soporta Video.
                                    </video>';
                 $inside .= '<div class="row" ' .  $inside_style . '>
                                <div class="col-sm-2" style="padding-top:2px;">Validez</div>
                                <div class="col-sm-1">Desde</div><div class="col-sm-2"><input style="width:100px;height:20px;line-height: 1;padding: 0 0;padding-top:-3px;font-size: 14px;" type="text" class="datepicker" name="inicio" value="' . $inicio . '" /></div>
                                <div class="col-sm-1">Hasta</div><div class="col-sm-2"><input style="width:100px;height:20px;line-height: 1;padding: 0 0;padding-top:-3px;font-size: 14px;" type="text" class="datepicker" name="expira" value="' . $expira . '" /></div>
                           </div>';
                 $inside .= '<div class="row" ' .  $inside_style . '>
                                <div class="col-sm-2" style="padding-top:2px;">Tpo Exp. (s)</div>
                                <div class="col-sm-2"><input class="form-control" style="height:18px;" type="text" name="exposure" value="' . $row['exposure'] . '"></div>
                            </div>';
                if ($row['days'] != NULL) $days = str_split($row['days']); else $days = array();
                $inside .= '<div class="row" ' .  $inside_style . '>
                            <div class="col-sm-2">Días</div>&nbsp;&nbsp;&nbsp;&nbsp;';
                                foreach($daysinweek as $key => $value) {
                                if (in_array($key, $days)) $checked=' checked="checked"'; else $checked='';
                                $inside.='<div class="form-check form-check-inline" style="padding-left: 2px;">
                                            &nbsp;
                                            <input name="days[]" type="checkbox" style="padding-left:2px;" value="' . $key . '"' . $checked . '>
                                            <label class="form-check-label" style="padding-left: 0rem;" for="inlineCheckbox1">' . $value . '</label>
                                            </div>';
                                }
                $inside .= '</div>';

                $select='<select name="status">';
                if ($row['status'] == 1)
                    $select .='<option selected value="1">Habilitado</option><option value="0">Desabilitado</option>';
                else
                    $select .='<option value="1">Habilitado</option><option selected value="0">Desabilitado</option>';
                $select .='</select>';

                $inside .= '<div class="row" ' .  $inside_style . '>
                               <div class="col-sm-2" style="padding-top:2px;">Status</div>
                               <div class="col-sm-2">' . $select . '</div>
                            </div>';
                $inside .= '<input type="hidden" name="tv" value="' . $tv . '">';
                $inside .= '<input type="hidden" name="orden" value="' . $orden . '">';

                switch ($row['codigo']) {
                    case 1:$inside .= '
                            <input type="hidden" name="html" value="' . $row['html'] . '">

                            <div class="row" ' .  $inside_style . '>
                               <div class="col-sm-2" style="padding-top:2px;">ID</div>
                               <div class="col-sm-4">' . $row['html']  . '</div>
                            </div>';
                            break;
                    case 2:$inside .= '<input type="hidden" name="html" value="' . $html . '">';
                                $inside .= '<div class="row" ' .  $inside_style . '>
                                    <div class="col-sm-2">Ancho (px)</div><div class="col-sm-2"><input class="form-control" style="height:18px;" type="text" name="ww" value="' . $row['w'] . '"></div>
                                    <div class="col-sm-2">Alto (px)</div><div class="col-sm-2"><input class="form-control" style="height:18px;" type="text" name="hh" value="' . $row['h'] . '"></div>
                                </div>
                                <div class="row" ' .  $inside_style . '>
                                    <div class="col-sm-2" style="padding-top:2px;">Archivo</div>
                                    <div class="col-sm-8">' . $code4file  . '</div>
                                </div>
                                <div class="row" ' .  $inside_style . '>
                                    <div class="col-sm-2" style="padding-top:2px;">Subir Arch.</div>
                                    <div class="col-sm-8"><input name="file" type="file" size="15" /></div>
                                </div>';
                                $inside .= '<div class="row" ' .  $inside_style . '>
                                  <div class="col-sm-12"><iframe width="625" height="480" srcdoc="' . $iframe . '"></iframe></div>
                                </div>';
                             break;
                    case 4:$inside .= '
                            <div class="row" ' .  $inside_style . '>
                               <div class="col-sm-2" style="padding-top:2px;">Fecha</div>
                               <div class="col-sm-4"><input style="width:100px;height:20px;line-height: 1;padding: 0 0;padding-top:-3px;font-size: 14px;" type="text" class="datepicker" name="fecha" value="' . $dt . '" /></div>
                            </div>
                            <div class="row" ' .  $inside_style . '>
                               <div class="col-sm-2" style="padding-top:2px;">Área</div>
                               <div class="col-sm-4"><input style="width:300px;height:20px;line-height: 1;padding: 0 0;padding-top:-3px;font-size: 14px;" type="text" class="form-control" name="html1" value="' . $acc_area . '" /></div>
                            </div>
                            <div class="row" ' .  $inside_style . '>
                               <div class="col-sm-2" style="padding-top:2px;">Tipo</div>
                               <div class="col-sm-4"><input style="width:300px;height:20px;line-height: 1;padding: 0 0;padding-top:-3px;font-size: 14px;" type="text" class="form-control" name="html2" value="' . $acc_tipo . '" /></div>
                            </div>';
                            break;
                    default:$inside .= '
                                <div class="row" ' .  $inside_style . '>
                                  <div class="col-sm-2" style="padding-top:2px;">Texto</div>
                                  <div class="col-sm-10"><textarea name="html" style="font-family: monospace;max-width: none;flex: none;' . $htmlDisabled . '" rows="10" cols="80">' . $row['html'] . '</textarea></div>
                                </div>';
                            break;
                }
            }
        }
        echo $inside;
    }

    function ccch_maestro_tv_post() {
        //var_dump($_POST);
         if ((isset($_POST['update_sec'])) && ($_POST['update_sec'] == 'Save')) {
            $counter = 1;
            $ids = explode(",",$_POST['secuencia']);
            $clean_sec = ccch_query("UPDATE web_tv SET orden = NULL where titulo is not null;");
            foreach ($ids as $key => $value) {
                $update_sec = ccch_query("UPDATE web_tv SET orden = " . $counter++ . " where idtv=" . $value);
            }
            foreach (glob("/tmp/ccch_tv_graph_dev*.*") as $filename) {
                unlink($filename);
            }
            error_log("Files Delete because secuence is updated");
        }
        if ((isset($_POST['update_dev'])) && ($_POST['update_dev'] == 'Save'))
        {
            $clean_tv_content = ccch_query("DELETE from web_tv_content;");
            foreach ($_POST['devices'] as $key => $value) {
                foreach ($value as $key2 => $value2) {
                    $reinsert_link=ccch_query("INSERT into web_tv_content (idtv,idtvdev) VALUES (" . $key . "," . $value2 . ");");
                }

            }
        }

        if (isset($_POST['moddata']) && ($_POST['moddata'] == "Edit")) {
            $w=0;
            $h=0;
            $f="null";
            $e="null";
            $i='null';
            $exposure='null';
            $codigo_post = getvar('codigo',0);
            if ((isset($_POST['status'])) && ($_POST['status'] == 1)) $status = 1; else $status=0;
            if (isset($_POST['orden'])) $orden = $_POST['orden']; else $orden='null';
            if (isset($_POST['days'])) $daysarrays = $_POST['days']; else $days='null';
            if ((isset($_POST['inicio'])) && ($_POST['inicio'] != '')) $i=$_POST['inicio'];
            if ((isset($_POST['expira']))  && ($_POST['expira'] != '')) $e=$_POST['expira'];
            if ((isset($_POST['exposure'])) && ($_POST['exposure'] != '')) $exposure=$_POST['exposure'];
            if (isset($_POST['ww'])) $w=$_POST['ww'];
            if (isset($_POST['hh'])) $h=$_POST['hh'];
            if (isset($_POST['html'])) $set_html=str_replace('\"','"',$_POST['html']);
            if (trim($w) == '') $w=0;
            if (trim($h) == '') $h=0;
            if ($w==0 || $w>1250) $w=1250;
            if ($h==0 || $h>955) $h=955;
            if (isset($_POST['fecha']))
                if (trim($_POST['fecha']) != '') $f=DBdate($_POST['fecha']);
            if (isset($_POST['inicio']))
                if (trim($_POST['inicio']) != '') $i=DBdate($_POST['inicio']);
            if (isset($_POST['expira']))
                if (trim($_POST['expira']) != '') $e=DBdate($_POST['expira']);
            $days = implode('',$daysarrays);
            if (isset($_POST['html1']) && isset($_POST['html2']) && $codigo_post == 4)
            {
                $accidentes = array();
                $accidentes[] =  $_POST['html1'];
                $accidentes[] =  $_POST['html2'];
                $set_html = serialize($accidentes);
            }
            if ($codigo_post == '')
            {
                $nameintable=False;
                $macintable=False;
                $names=[];
                $macs=[];
                $query=ccch_query("select * from web_tv_devices where idtvdev <>" . $_POST['idtv'] . " and dev_mac='" . $_POST['dev_mac'] . "'");
                while ($row = ccch_fetch($query)) {
                    $names[]=$row['dev_name']=trim($row['dev_name']);
                    $macs[]=$row['dev_mac']=trim($row['dev_mac']);
                }
                if (in_array($_POST['dev_mac'],$macs)) $macintable=True;
                if (in_array($_POST['dev_name'],$names)) $nameintable=True;
                if ($macintable)
                {
                    displayAlert("Ya existe otro dispositivo con esa MAC Address, no se ha ejecutado ninguna acción", "danger");
                } else
                {
                    if ($nameintable) displayAlert("Ya existe otro dispositivo con ese nombre, no se ha ejecutado ninguna acción", "danger");
                    else $update_webtvdev = ccch_query("update web_tv_devices set dev_name = '" . $_POST['dev_name'] . "', dev_mac= '" . $_POST['dev_mac'] . "' , state = " . $_POST['status'] . "  where idtvdev =". $_POST['idtv']);
                }

            }

                else
                {
                    $update_webtv = ccch_query("update web_tv set
                                          html='" . $set_html . "',
                                          titulo='" . $_POST['titulo'] . "',
                                          icono='" . $_POST['icono'] . "',
                                          orden=" . $orden . ",
                                          w=" . $w . ",
                                          h=" . $h . ",
                                          fecha=" . $f . ",
                                          expira=" . $e . ",
                                          inicio=" . $i . ",
                                          exposure=" . $exposure . ",
                                          status=" . $status . ",
                                          days='" . $days . "'
                        where idtv=" . $_POST['idtv'] . ";");
                }
        }
        if (($_POST['codigo'] == 2) && (isset($_FILES['file']))) {

            if (!function_exists('wp_handle_upload')) require_once( ABSPATH . 'wp-admin/includes/file.php' );
            $uploadedfile = $_FILES['file'];
            $upload_overrides = array( 'test_form' => false );
            $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
            if ($movefile && ! isset($movefile['error'])) {
                displayAlert("Archivo cargado correctamente",'success');
                $ext = pathinfo( $movefile['file'], PATHINFO_EXTENSION);
                $filename='/var/www/html/wordpress/wp-content/uploads/tv/' . $_POST['idtv'];
                 if (file_exists($filename.'.'.$ext)) unlink($filename.'.'.$ext);
                copy($movefile['file'],$filename.'.'.$ext);
                error_log("copie archivo: " . $movefile['file'] . " a archivo: " . $filename.'.'.$ext);
                $update_webtv = ccch_query("update web_tv set html='" . $ext . "' where idtv=" . $_POST['idtv'] . ";");
                } //else displayAlert("Alerte de este error: " . $movefile['error'], 'danger');
        }

        if (isset($_POST['moddata']) && ($_POST['moddata'] == "Erase"))
        {
            if ($tv ==5) $delete=ccch_query("DELETE FROM web_tv_content where idtvdev=" . $_POST['idtv']);
            if ($tv ==5) $delete=ccch_query("DELETE FROM web_tv_devices where idtvdev=" . $_POST['idtv']);
            if ($tv <=4)
                {
                    $delete=ccch_query("DELETE FROM web_tv where idtv=" . $_POST['idtv']);
                    $filename='/var/www/html/wordpress/wp-content/uploads/tv/' . $_POST['idtv'];
                    foreach (glob($filename . "*") as $file) {
                        if(is_file($file)) unlink($file);
                    }
                }
        }

        if (isset($_POST['modnew']) && ($_POST['modnew'] == "Save"))
        {
            $nameintable=False;
            $macintable=False;
            $names=[];
            $macs=[];
            $query=ccch_query("select * from web_tv_devices where dev_mac='" . $_POST['dev_mac_new'] . "'");
            while ($row = ccch_fetch($query)) {
                $names[]=$row['dev_name']=trim($row['dev_name']);
                $macs[]=$row['dev_mac']=trim($row['dev_mac']);
            }
            if (in_array($_POST['dev_mac'],$macs)) $macintable=True;
            if (in_array($_POST['dev_name'],$names)) $nameintable=True;
            if ($nameintable)
            {
                displayAlert("Ya existe un dispositivo con esa MAC Address, no se ha ejecutado ninguna acción", "danger");
            } else
                {
                    if ($nameintable) displayAlert("Ya existe otro dispositivo con ese nombre, no se ha ejecutado ninguna acción", "danger");
                    else $insert=ccch_query("INSERT INTO web_tv_devices (dev_name, dev_mac,dev_ip,state) VALUES ('" . $_POST['dev_name_new'] . "','" . $_POST['dev_mac_new'] . "', NULL, " . $_POST['state'] . ")");
                }

        }
    }

    function ccch_maestro_tv_add() {
        $cnt=0;
        $query=ccch_query("SELECT count(*) as cnt from web_tv where status=2 and codigo = 3 and isnull(titulo,'')=''");
        while ($row = ccch_fetch($query)) {
            $cnt=$row['cnt'];
        }
        if ($cnt == 0) ccch_query("insert into web_tv(codigo,icono,titulo,html,status, exposure,days) values(3,'fa-hourglass','','',2,90,'2345671')");
        $cnt=0;
        $query=ccch_query("SELECT count(*) as cnt from web_tv where status=2 and codigo = 2 and isnull(titulo,'')=''");
        while ($row = ccch_fetch($query)) {
            $cnt=$row['cnt'];
        }
        if ($cnt == 0) ccch_query("insert into web_tv(codigo,orden,icono,titulo,html,status, exposure,days) values(2,999,'Imagen','','',2,90,'2345671')");
        $cnt=0;
        $query=ccch_query("SELECT count(*) as cnt from web_tv where status=2 and codigo = 5 and isnull(titulo,'')=''");
        while ($row = ccch_fetch($query)) {
            $cnt=$row['cnt'];
        }
        if ($cnt == 0) ccch_query("insert into web_tv(codigo,icono,titulo,html,status, exposure,days) values(5,'fa-hourglass','','',2,90,'2345671')");

    }

    function ccch_maestro_tv($atts,$content=null) {
        global $tablexls;

        $html = '';
        $style=' style="padding-top:5px;padding-left:0px;padding-right:0px;margin-left:0px;margin-right:0px;"';
        $access=ccch_euser();
        $a=ccch_access_r($access);
        if ($a)  return $a;
        $conn=ccch_connect();
        ccch_maestro_tv_post();
        $tv=getvar('tv', 0);
        if ($tv < 5) ccch_maestro_tv_add();
        $html .='<form method="POST" id="form">';
        $html .=STITLE('TV' , ccch_menu_text('tv',array('Imágenes','Gráficos','Información','Marquee','Accidentes','Dispositivos','Secuencia'),0,'color:white;background-color: ' . $colors['caption_background'] . ';font-size: 15px;','#292b2c'),1);
        $html .='</form>';
        $in='1,2,3,4,5,6,7,8,9,10';
        if ($tv == 5)
        {
            $alldevices=array();

            $inside_style = ' style="padding-top:5px;padding-left:0px;padding-right:0px;margin-left:0px;margin-right:0px;"';
            $query=ccch_query("SELECT DISTINCT * from web_tv_devices order by dev_name asc");
            while ($row = ccch_fetch($query)) {
                $row['idtvdev']=trim($row['idtvdev']);
                $alldevices[$row['idtvdev']] = trim($row['dev_name']);
            }
            //var_dump($alldevices);
            $seconndrow = '';
             $html .=   '<form method="POST" id="asigned-devices">
                        <input type="hidden" id="tv" name="tv" value="' . $tv . '">';
            $query0=ccch_query("SELECT * from web_tv_devices order by dev_name asc");
            $title = 'Edición Elemento/Dispositivo';
            $html .= '<input type="hidden" id="tv" name="tv" value="' . $tv . '">';
            $html .= STABLE('TABLE');
            $html .= STABLE('ROW HEADER');
            $html .= STABLE('COL HEADER','Ítem','RL;text-align:center;');
            $html .= STABLE('COL HEADER','Tipo','text-align:left;');
            $html .= STABLE('COL HEADER','Días','text-align:left;');
            $html .= STABLE('COL HEADER','Exposición','text-align:center;');
            while ($row0 = ccch_fetch($query0)) {
                if ($row0["state"] == False) $style_h='text-decoration: line-through;'; else $style_h='';
                $edit ='<span style="padding-left:8px;cursor:pointer;">
                        <i class="fa fa-edit"
                            idtv="' . trim($row0['idtvdev']) . '"
                            tv="' .  $tv . '"
                            codigo="' . trim($row2['codigo']) . '"
                            data-tooltip="tooltip"
                            data-placement="top" id="' . trim($row0['idtvdev']) . '"></i></span>';
                $html .= STABLE('COL HEADER',$edit.'&nbsp;&nbsp;'.$row0["dev_name"],'RR;text-align:left;'.$style_h);

            }

            $html .= STABLE('ROW HEADER END');


            $query2=ccch_query("SELECT * from (SELECT *,isnull(DATEDIFF(day,SYSDATETIME(),expira),1) as df from web_tv where status = 1 and codigo in (" . $in . ")) tbl where df>0 order by status desc, orden asc,idtv desc;");
            while ($row2 = ccch_fetch($query2)) {
                $style2 = '';
                $edit2 ='<span style="padding-left:8px;cursor:pointer;">
                        <i class="fa fa-edit"
                            idtv="' . $row2['idtv'] . '"
                            tv="' .  $tv . '"
                            codigo="' . $row2['codigo'] . '"
                            data-tooltip="tooltip"
                            data-placement="top" id="' . $row2['idtv'] . '"></i></span>';
                if ($row2['fecha'] == '' ) {
                    $fecha='';
                }
                else {
                    $fecha='Último accidente: ' . date('d-m-Y',strtotime(trim($row2['fecha'])));
                    $row2['html']=$fecha;
                }

                $tipo='Otro';
                switch ($row2['codigo']) {
                    case 1: $tipo='Gráfico'; break;
                    case 2: $tipo='Imagen'; $row2['html'] = $row2['w'].'x'.$row2['h'];break;
                    case 3: $tipo='Información'; break;
                    case 4: $tipo='Accidentes'; break;
                    case 5: $tipo='Marquee'; break;
                }
                if ($row2['status'] == 0 || $row2['df'] <=0 ) $style2='color:gray;text-decoration: line-through;'; else $style2='color: black;';
                if ($row2['status'] == 0) $style2='color:gray;text-decoration: line-through;';
                //if ($row2['df'] <=0 ) $style2='color:blue;text-decoration: line-through;';
                if ($row2['status'] == 2 && trim($row2['titulo']) == '') $style2='color:#e8500e;';
                $query3=ccch_query("SELECT idtvdev from web_tv_content where idtv=" . $row2['idtv']);
                $dev=array();
                while ($row3 = ccch_fetch($query3)) {
                    $dev[]=$row3['idtvdev']=trim($row3['idtvdev']);
                }
                //var_dump($dev);
                if ($row2['days'] != NULL) $days1 = str_split(trim($row2['days'])); else $days1 = array();
                $daysinweek = array(
                                "2"=> "Lu",
                                "3"=> "Ma",
                                "4"=> "Mi",
                                "5"=> "Ju",
                                "6"=> "Vi",
                                "7"=> "Sa",
                                "1"=> "Do"
                            );
                $first = True;
                $days = '';
                foreach ($days1 as $key => $value) {
                    if ($first) $days .= $daysinweek[$value]; else $days .= ','. $daysinweek[$value];
                    $first = False;
                }
                if (trim($row2['days']) == '2345671') $days = "Todos";
                $html .= STABLE('ROW');
                $html .= STABLE('COL',$edit2.'&nbsp;&nbsp;'.trim($row2['titulo']),'text-align:left;'.$style2);
                $html .= STABLE('COL',$tipo,'text-align:left;'.$style2);
                $html .= STABLE('COL',$days,'text-align:left;'.$style2);
                $html .= STABLE('COL',trim($row2['exposure']),'text-align:center;'.$style2);
                foreach($alldevices as $key => $value) {
                    if (in_array($key, $dev)) $checked=' checked="checked"'; else $checked='';
                    $html .= STABLE('COL','<input name="devices[' . $row2['idtv'] . '][]" type="checkbox" value="' . $key . '"' . $checked . '>','text-align:center;'.$style2);
                }

                $html .= STABLE('ROW END');

            }
            $html .= STABLE('TABLE END');
            $html .='<br><div class="form-footer" style="text-align: center; cursor: default;">
                                    <button type="button" ' . $disabled_write . 'class="btn btn-primary" data-toggle="modal" data-target="#modalCrea">Agregar Disp.</button>
                                    <button type="submit" ' . $disabled_write . ' class="btn btn-success" name="update_dev" id="update_dev" value="Save">Grabar</button>
                                </div></form>';
        }
        if ($tv == 6 )
        {
            $cap=ccch_user();
            if ($cap['w']== True) $disabled_write=''; else  $disabled_write='disabled';
            $html .=   '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
                        <form method="POST" id="sortable-secuencia">
                        <input type="hidden" id="secuencia" name="secuencia" value="">
                        <input type="hidden" id="tv" name="tv" value="' . $tv . '">
                        <div style="text-align: center; cursor: default;">
                            <ul id="sortable" class="sortable ui-sortable" style="text-align: left; padding-left: 100px; padding-right: 100px;cursor: pointer; display:inline-table;">';
                            $query=ccch_query("SELECT * from (SELECT *,isnull(DATEDIFF(day,SYSDATETIME(),expira),1) as df from web_tv where status = 1 and codigo in (1,2)) tbl where df>0 order by orden asc,idtv desc;");
                            $cont_Sec = 1;
                            while ($row = ccch_fetch($query)) {
                                if ($row['codigo'] == 1) $class = 'Gráficos'; else $class = 'Imagén/Texto';
                                $html .=   '<li id = "' . trim($row['idtv']) . '" class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span> ' . $class . ' - ' . trim($row['titulo']) . '</li>';
                            }
                            $html .= '</ul><br>
                        </div>';

            $html .='<br><br><div class="footer" style="text-align: center; cursor: default;">
                                    <button type="submit" ' . $disabled_write . ' class="btn btn-success" name="update_sec" id="update_sec" value="Save">Grabar</button>
                                </div></form>
                                <script>
                                    $(function() {
                                        $("#sortable").sortable({
                                             placeholder: "ui-state-highlight",
                                             helper: \'clone\',
                                             create: function(event, ui) {
                                             var secuencia = $(this).sortable(\'toArray\')
                                            $("#secuencia").val(secuencia);
                                            console.log(secuencia);
                                            },
                                             update: function(event, ui) {
                                             var secuencia = $(this).sortable(\'toArray\')
                                            $("#secuencia").val(secuencia);
                                            console.log(secuencia);
                                            }
                                        });
                                        $("#sortable").disableSelection();
                                    });
                                </script>';
        }
         if ($tv <= 4 )  {
            $title = 'Modificación';
                switch ($tv) {
                case '0': $in='2';break;
                case '1': $in='1';break;
                case '2': $in='3';break;
                case '3': $in='5';break;
                case '4': $in='4';break;
            }
            $space = True;
            $html .= STABLE('TABLE');
            $html .= STABLE('ROW HEADER');
            $html .= STABLE('COL HEADER','Código','RL;text-align:center;');
            $html .= STABLE('COL HEADER','Titulo','text-align:left;');
            $html .= STABLE('COL HEADER','Ícono','text-align:left;');
            $html .= STABLE('COL HEADER','Texto/ID','text-align:left;');
            $html .= STABLE('COL HEADER','Expira','RR;text-align:left;');
            $html .= STABLE('ROW HEADER END');
            $query=ccch_query("SELECT *,isnull(DATEDIFF(day,SYSDATETIME(),expira),1) as df  from web_tv where codigo in (" . $in . ") order by status desc, df desc, orden asc,idtv desc");
            while ($row = ccch_fetch($query)) {
                $edit ='<span style="padding-left:8px;cursor:pointer;">
                        <i class="fa fa-edit"
                            id=originaledit
                            idtv="' . $row['idtv'] . '"
                            tv="' .  $tv . '"
                            codigo="' . $row['codigo'] . '"
                            data-tooltip="tooltip"
                            data-placement="top" id="' . $row['idtv'] . '"></i></span>';
                if ($row['fecha'] == '' ) {
                    $fecha='';
                }
                else {
                    $fecha='Último accidente: ' . date('d-m-Y',strtotime($row['fecha']));
                    $row['html']=$fecha;
                }
                if ($row['expira'] == '' )
                    $expira='Nunca';
                else
                    $expira=date('d-m-Y',strtotime($row['expira']));

                $tipo='Otro';
                switch ($row['codigo']) {
                    case 1: $tipo='GRAFICO'; break;
                    case 2: $tipo='IMAGEN/TEXTO'; $row['html'] = $row['w'].'x'.$row['h'];break;
                    case 3: $tipo='INFO'; break;
                    case 4: $tipo='ACCIDENTES'; break;
                    case 5: $tipo='MARQUEE'; break;
                }
                if ($row['status'] == 0 || $row['df'] <=0 ) $style='color:gray;text-decoration: line-through;'; else $style='color: black;';
                //if ($row['df'] <=0 ) $style='color:blue;text-decoration: line-through;';
                if ($row['status'] == 2 && trim($row['titulo']) == '') $style='color:#e8500e;';
                if (($space) && (($row['df'] <=0) || ($row['status'] == 0)))
                {
                    $html .= STABLE('ROW');
                    $html .= STABLE('COL','&nbsp;&nbsp;','text-decoration: none;font-size: 12px;text-align:left;');
                    $html .= STABLE('COL');
                    $html .= STABLE('COL','&nbsp;&nbsp;','text-decoration: none;font-size: 12px;text-align:left;');
                    $html .= STABLE('COL','&nbsp;&nbsp;','text-decoration: none;font-size: 12px;text-align:left;');
                    $html .= STABLE('COL');
                    $html .= STABLE('ROW END');
                    $space = false;
                }
                $html .= STABLE('ROW');
                $html .= STABLE('COL',$edit.'&nbsp;&nbsp;'.$tipo,'text-align:left;'.$style);
                $html .= STABLE('COL',$row["titulo"],'text-align:left;'.$style);
                $html .= STABLE('COL',$row['icono'],'text-align:left;'.$style);
                if (strlen($row['html']) > 60)
                    $html .= STABLE('COL',substr(str_replace('>','',str_replace('<','',$row['html'])),0,60).' ....' ,'text-align:left;'.$style);
                else
                     $html .= STABLE('COL',str_replace('>','',str_replace('<','',$row['html'])) ,'text-align:left;'.$style);
                $html .= STABLE('COL',$expira,'text-align:left;'.$style);
                $html .= STABLE('ROW END');

            }
            $html .= STABLE('TABLE END');
        }
        $cap=ccch_user();
        if ($cap['w']== True) $disabled_write=''; else  $disabled_write='disabled';
        if ($cap['d']== True) $disabled_delete=''; else  $disabled_delete='disabled';
        $html .= '<form method="POST" id="validate2">
                    <input type="hidden" id="idtvdev" name="idtv" value="">
                    <input type="hidden" id="tv" name="tv" value="' . $tv . '">
                    <div class="modal fade" id="modalCrea" tabindex="-1" role="dialog" aria-labelledby="modalCrea" aria-hidden="true">
                        <div class="modal-dialog  modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 style="margin-right: 10px;" class="modal-title" id="modalCrea">Nuevo Dispositivo</h5>
                                    <button style="margin-top:-30px;margin-right:-10px;color:red;opacity:1;font-size: 30px;" type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Nombre Dispositivo</label></div>
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="dev_name_new" type="text" value="" required="required" ></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">MAC Dispositivo</label></div>
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="dev_mac_new" type="text" value="" required="required" ></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Estado inicial</label></div>
                                            <div class="col-xs-3 col-sm-3 col-lg-5">
                                                <select name="state">
                                                    <option selected value="1">Habilitado</option>
                                                    <option value="0">Desabilitado</option>
                                                </select>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" ' . $disabled_write . ' class="btn btn-success" name="modnew" id="modnew" value="Save">Grabar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>';
                $html .= "<script>
                        jQuery(document).ready(function(){
                            idtv=$(this).attr('idtv');
                            tv=$(this).attr('tv');
                            $('#idtv').val(idtv);
                            $('#tv').val(tv);
                        });
                    </script>";
        $html .= '<form method="POST" id="validateUP" enctype="multipart/form-data">
                  <input type="hidden" id="idtv" name="idtv" value="">
                  <input type="hidden" id="codigo" name="codigo" value="">
                  <input type="hidden" id="tv" name="tv" value="' . $tv . '">
                  <div class="modal fade" id="modmodal" tabindex="-1" role="dialog" aria-labelledby="modModalLabel" aria-hidden="true">
                      <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 style="margin-right: 10px;" class="modal-title" id="modModalLabel">' . $title . '</h5>
                            <button style="margin-top:-30px;margin-right:-10px;color:red;opacity:1;font-size: 30px;" type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">

                            <div class="container-fluid"><div id="inside"></div></div>

                          </div>
                          <div class="modal-footer">';
                            if ($tv == 0 || $tv == 2 || $tv == 3) $html .='<button type="submit" ' . $disabled_delete . 'class="btn btn-danger" name="moddata" id="moddata" value="Erase">Eliminar</button>';
                            $html .='<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success" name="moddata" id="moddata" value="Edit">Modificar</button>
                          </div>
                        </div>
                      </div>
                 </div>
                 </form>';

        $html .="<script>
                $(document).ready(function() {
                    jQuery('.fa-edit').click(function(e) {
                        var tm = new Date().getTime();
                        idtv=$(this).attr('idtv');
                        tv=$(this).attr('tv');
                        codigo=$(this).attr('codigo');
                        console.log(idtv,tv,codigo);
                        var savethis=this;
                        var url = '/wp-content/plugins/ccch/ccch_maestros/ccch_maestros_tv.php?idtv=' + idtv + '&tv=' + tv + '&codigo=' + codigo;
                        $(this).removeClass('fa-edit').addClass('fa-spin').addClass('fa-spinner')
                        //$('.inside').load(url,function(result){}); // for some reason toogle button only works on second load.
                        $('#inside').load(url,function(result){
                            $('.mysummernote').summernote({
                                lang: 'es-ES' // default: 'en-US'
                            });
                            $('#idtv').val(idtv);
                            $('#codigo').val(codigo);
                            $('#tv').val(tv);
                            $('.datepicker').datepicker({dateFormat : 'dd-mm-yy' });
                            $('#modmodal').modal('show');
                            $(savethis).removeClass('fa-spin').removeClass('fa-spinner').addClass('fa-edit');
                        });
                    });
                });
              </script>";
        return $html;
    }


?>