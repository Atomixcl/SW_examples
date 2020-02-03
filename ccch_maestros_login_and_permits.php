<?php
    function displayAlert($text, $type) {
        echo  "<div class=\"alert alert-".$type."\" role=\"alert\">
                   <p>".$text."</p>
                   </div>";
    }

    function ccch_usercap($arrayCaps,$disabled='') {
        if (isPdf() || isExcel()) {
            return true;
        }
        $caps=false;
        $arrayCaps[]='administrator';
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            foreach ($arrayCaps as $value) {
                $caps = $caps || current_user_can( $value );
            }
        }
        if ($disabled == '' && $caps) return true;
        if ($disabled == '' && !$caps) return false;
        if ($caps) return '';
        return ' ' . $disabled . ' ';
    }

    function ccch_access_r($access) {
        $html = '';
		$style='';
        if (isPdf()) $style=' style="font-size:12px;" ';
        if (!isset($access['r'])) {
            return $html .= '<div  ' . $style . ' class="alert alert-danger" role="alert">
                        Página ha expirado o acceso denegado.
                      </div>';
        }
        if ($access['r'] || $access['w']) {
            return false;
        }  else {
            $html .= '<div   ' . $style . ' class="alert alert-danger" role="alert">
                        Acceso denegado.
                      </div>';
            if (function_exists('is_user_logged_in')) {
                if (!is_user_logged_in()) {
                    $html .='<script>
                              window.location = "/wp-login.php";
                             </script>';
                }
            }
            return $html;
        }
    }

    function ccch_useraccess($arrayCaps) {
        $html = '';
        if (!is_user_logged_in()) {
            $html .= '<div class="alert alert-danger" role="alert">
                        Login Requerido
                      </div>';
            if (function_exists('is_user_logged_in')) {
                if (!is_user_logged_in()) {
                    $html .='<script>
                              window.location = "/wp-login.php";
                             </script>';
                }
            }
            return $html;
        }
        if (!ccch_usercap($arrayCaps)) {
            $html .= ' <div class="alert alert-danger" role="alert">
                        Acceso denegado
                      </div>';

            return $html;
        }
        return false;
    }

    function ccch_euser($userid=0, $postid=0) {
        $e=getvar('e', '');
        $f=getvar('f', '');
        if ($e=='' && isset($_SESSION['e'])) $e=$_SESSION['e'];
        if ($f=='' && isset($_SESSION['f'])) $e=$_SESSION['f'];
        if ($e != '' && $f != '') {
            $dec=explode(' ',decryptOpen($e));
            if (!isset($dec[0])) $dec[0] = 10;
			if (!isset($dec[1])) $dec[1] = '';

            $userid=$dec[1];
            $clock1=$dec[0];

            $dec=explode(' ',decryptOpen($f));
			if (!isset($dec[0])) $dec[0] = 10;
			if (!isset($dec[1])) $dec[1] = '';
            $postid=$dec[1];
            $clock2=$dec[0];

            // error_log($clock1+1000 . ' ' . date('U'));
            if ($clock1 == $clock2 && $clock2+30000 > date('U'))
                return ccch_user($userid,$postid);
            else
                return 'Página expiró. Por favor recarge página.';
        }  else return ccch_user($userid, $postid);
    }

    function ccch_user($userid=0, $postid=0)
    {
        $con=ccch_connect();
        $username='';
        $nicename='';
        $cap=array();
        $cap['postid']=-1;
        $cap['userid']=-1;
        $cap['epostid']='';
        $cap['euserid']='';
        $cap['r'] = false;
        $cap['w'] = false;
        $cap['e'] = false;
        $cap['d'] = false;
        $cap['loggedin'] = false;
        $cap['text'] = '';
        $cap['username'] = '';
        $cap['planta'] = -1;
        $cap['nicename'] = -1;
        $cap['t1'] = '';
        $cap['t2'] = '';
        $cap['t3'] = '';
        $cap['departamento'] = '';
        $cap['editrollo'] = false;

        if (function_exists('is_user_logged_in') && ($userid == 0 || $postid == 0)) {
            global $wpdb;
            if (get_post()) $postid = get_post()->ID;
            if (is_user_logged_in()) {
                $cap['loggedin'] = true;
                $current_user = wp_get_current_user();
                $userid=$current_user->ID;
                $query = 'select * from wp_users where ID=' . $userid;
                $r = $wpdb->get_results( $query );
                foreach ( $r as $cls ) {}
                $nicename=$cls->display_name;
                $username=$cls->user_login;
                $cap['postid']=$postid;
            } else return $cap;
        } else {
            $sqlconn=ccch_connect_pdo();
            $sql = $sqlconn->prepare('select * from wp_users where ID=:userid');
            $sql->execute(array('userid' => $userid));
            $query = $sql->fetchAll();
            foreach ($query as $row) {
                $nicename=$row['display_name'];
                $username=$row['user_login'];
            }
        }

        $cap['username'] = $username;
        $cap['nicename'] = $nicename;
        $cap['planta'] = '';
        $cap['postid']=$postid;
        $cap['userid']=$userid;
        $cap['epostid']=encryptOpen(date('U') . ' ' . $postid);
        $cap['euserid']=encryptOpen(date('U') . ' ' . $userid);
        if ($userid == 1 && ((function_exists('is_user_logged_in') && is_user_logged_in()) || isPdf() || isExcel())) {
            $cap['r'] = true;
            $cap['w'] = true;
            $cap['e'] = true;
            $cap['d'] = true;
            $cap['loggedin'] = true;
            $cap['text'] = '';
            $cap['username'] = $username;
            $cap['nicename'] = 'Mero Mero';
            $cap['planta'] = 0;
            $cap['t1'] = '';
            $cap['t2'] = '';
            $cap['t3'] = '';
            $cap['departamento'] = '0014';
            $cap['editrollo'] = true;

            return $cap;
        }
        $query=ccch_query("select web_maestros_usuarios.id_wp_users, web_maestros_usuarios.id_rol,iddepto,id_plantas,rol_read, rol_write, rol_delete, rol_extra, rol_t1, rol_t2, rol_t3,rol_alloweditrollo,user_alloweditrollo  from web_maestros_usuarios
                            left join web_maestros_rolpost on web_maestros_usuarios.id_rol=web_maestros_rolpost.id_rol and web_maestros_rolpost.id_wp_post=" . $postid. "
                            left join web_maestros_rol on  web_maestros_usuarios.id_rol=web_maestros_rol.id_rol
                            left join web_maestros_planta on web_maestros_usuarios.id_wp_users=web_maestros_planta.id_wp_users
                            where web_maestros_usuarios.id_wp_users=" . $userid);
        while ($row = ccch_fetch($query)) {
            $row['id_plantas']=trim($row['id_plantas']);
            $row['rol_read']=trim($row['rol_read']);
            $row['rol_write']=trim($row['rol_write']);
            $row['rol_delete']=trim($row['rol_delete']);
            $row['rol_extra']=trim($row['rol_extra']);
            $row['iddepto']=trim($row['iddepto']);
            $row['rol_alloweditrollo']=trim($row['rol_alloweditrollo']);
            $row['user_alloweditrollo']=trim($row['user_alloweditrollo']);
            if ($row['rol_read'] == 1 ) $cap['r'] = True;
            if ($row['rol_write'] == 1 ) $cap['w'] = True;
            if ($row['rol_delete'] == 1 ) $cap['d'] = True;
            if ($row['rol_extra'] == 1 ) $cap['e'] = True;
            if ($row['rol_t1'] != "" ) $cap['t1'] = $row['rol_t1'];
            if ($row['rol_t2'] != "" ) $cap['t2'] = $row['rol_t2'];
            if ($row['rol_t3'] != "" )  $cap['t3'] = $row['rol_t3'];
            $cap['planta'] = $row['id_plantas'];
            $cap['departamento'] = $row['iddepto'];
            if (($row['user_alloweditrollo'] == 1 ) || ($row['rol_alloweditrollo'] == 1 )) $cap['editrollo'] = True;
        }
        $cap['text'] = 'Mensaje';
        return $cap;
    }

    function ccch_maestros_rols()
    {
        global $wpdb;
        global $wp;
        $current_url = home_url(add_query_arg(array(),$wp->request));
        $html = '';

        $access=ccch_euser();
        $a=ccch_access_r($access);
        if ($a)  return $a;

        $html = '<style>
                            .spacer5 {height: 15px;}
                    </style>';
        $htmllast='';
        $htmllast2='';
        $con=ccch_connect();
        $html .='<form method="POST" name="go" id="go">';
        if (isset($_POST["idrol"])) $idrol = $_POST["idrol"]; else $_POST["idrol"] = -1;
        if (isset($_GET["idrol"])) $idrol = $_GET["idrol"]; else $_GET["idrol"] = -1;
        //var_dump($_POST);
        //POST CANCEL
        if (isset($_POST["Rols"]) && ($_POST["Rols"] == 'Dismiss')) echo '<META HTTP-EQUIV="Refresh" Content="1; URL=' . $current_url . '">';
        //POST SAVE
        if (isset($_POST["Rols"]) && ($_POST["Rols"] == 'Save'))
        {
            $delete=ccch_query('DELETE FROM web_maestros_usuarios where id_rol=' . $_POST['idrol']);
            $set_editrollo = ccch_query('update web_maestros_rol set rol_alloweditrollo =' . $_POST['rol_alloweditrollo'] . ' where id_rol=' . $_POST['idrol']);
            foreach ($_POST['assignedusers'] as $assignedID) {
                $insert=ccch_query("INSERT INTO web_maestros_usuarios (id_wp_users, id_rol) VALUES (" . $assignedID . ", " . $_POST['idrol'] . ")");
            }
        }
        //POST DELETE ROL
        if (isset($_POST["Rols"]) && ($_POST["Rols"] == 'Delete'))
        {
           $delete=ccch_query("DELETE FROM web_maestros_rol where id_rol=" . $_POST['rol2delete']);
           $delete=ccch_query("DELETE FROM web_maestros_usuarios where id_rol=" . $_POST['rol2delete']);
           $delete=ccch_query("DELETE FROM web_maestros_rolpost where id_rol=" . $_POST['rol2delete']);
        }
        //POST NEW ROL
        if (isset($_POST["Rols"]) && ($_POST["Rols"] == 'New'))
        {
            $nameintable=False;
            $names=[];
            $query=ccch_query("select * from web_maestros_rol");
            while ($row = ccch_fetch($query)) {
                $row['rol']=trim($row['rol']);
                $names[]=$row['rol'];
            }
            if (in_array($_POST['new_rol'],$names)) $nameintable=True;
            if ($nameintable)
            {
                displayAlert("Ya existe otro Rol con ese nombre, no se ha ejecutado ninguna acción", "danger");
            } else
            {
                $rolid2copy=-1;
                $new_rol_alloweditrollo=0;
                if (!isset($_POST['new_rol_alloweditrollo']) || ($_POST['new_rol_alloweditrollo'] == "")) $new_rol_alloweditrollo=0; else $new_rol_alloweditrollo=$_POST['new_rol_alloweditrollo'];
                $insert=ccch_query("INSERT INTO web_maestros_rol (rol,rol_alloweditrollo) VALUES ('" . $_POST['new_rol'] . "'," . $new_rol_alloweditrollo . ")");
                sleep(3);
                $query=ccch_query("select id_rol from web_maestros_rol where rol='" . $_POST['new_rol'] . "'");
                        while ($row = ccch_fetch($query)) {
                            $row['id_rol']=trim($row['id_rol']);
                            $rolid2copy=$row['id_rol'];
                            //var_dump($rolid2copy);
                        }
                if ($rolid2copy > 0)
                {
                    //Copying Users
                    $query=ccch_query("select * from web_maestros_usuarios where id_rol=" . $_POST['basedrol']);
                        while ($row = ccch_fetch($query)) {
                            $row['id_wp_users']=trim($row['id_wp_users']);
                            $insert=ccch_query("INSERT INTO web_maestros_usuarios (id_wp_users, id_rol) VALUES (" . $row['id_wp_users'] . ", " . $rolid2copy . ")");
                        }
                    //Copyinf Permits
                    $query=ccch_query("select * from web_maestros_rolpost where id_rol=" . $_POST['basedrol']);
                        while ($row = ccch_fetch($query)) {
                            $row['id_wp_post']=trim($row['id_wp_post']);
                            $row['post_title']=trim($row['post_title']);
                            $row['rol_read']=trim($row['rol_read']);
                            $row['rol_write']=trim($row['rol_write']);
                            $row['rol_delete']=trim($row['rol_delete']);
                            $row['rol_extra']=trim($row['rol_extra']);
                            $row['rol_t1']=trim($row['rol_t1']);
                            $row['rol_t2']=trim($row['rol_t2']);
                            $row['rol_t3']=trim($row['rol_t3']);
                            $insert=ccch_query("INSERT INTO web_maestros_rolpost (id_rol, id_wp_post, rol_read, rol_write, rol_delete, rol_extra, rol_t1, rol_t2, rol_t3) VALUES (" . $rolid2copy . ", " . $row['id_wp_post'] . ", " . $row['rol_read'] . ", " . $row['rol_write'] . ", " . $row['rol_delete'] . ", " . $row['rol_extra'] . ", '" . $row['rol_t1'] . "', '" . $row['rol_t1'] . "', '" . $row['rol_t1'] . "')");

                        }
                }
            }
        }
        // POST RENAME ROL
        if ((isset($_POST["Rols"]) && ($_POST["Rols"] == 'Rename')))
        {
           if ($_POST['oldname'] != $_POST['rename_rol'])
           {
                $nameintable=False;
                $names=[];
                $query=ccch_query("select * from web_maestros_rol");
                while ($row = ccch_fetch($query)) {
                    $row['rol']=trim($row['rol']);
                    $names[]=$row['rol'];
                }
                if (in_array($_POST['rename_rol'],$names)) $nameintable=True;
                if ($nameintable)
                {
                    displayAlert("Ya existe otro Rol con ese nombre, no se ha ejecutado ninguna acción", "danger");
                } else
                {
                    $update==ccch_query("UPDATE web_maestros_rol set rol='" . $_POST['rename_rol'] . "' where  id_rol=" .$_POST['id_rename']);
                    displayAlert("El Rol ha sido cambiado a: " . $_POST['rename_rol'], "danger");
                }
           } else
           {
                displayAlert("El nombre es el mismo, no se ha ejecutado ninguna acción", "warning");
           }

        }
        $query=ccch_query("select * from web_maestros_rol");
        $html .= '<div class="container-fluid">
                    <div class="row">
                        <div class="col-xs-3 col-sm-3 col-lg-5">Nombre de Rol </div>
                        <div class="col-xs-3 col-sm-3 col-lg-5"><select id="idrol" type="text" name="idrol" onchange="this.form.submit()" style="padding: 0px 0px;font-size:14px;">';
        if ($_POST["idrol"] == -1) $html .= '<option selected value=-1>Seleccione un Rol</option>'; else $html .= '<option value=-1>Seleccione un Rol</option>';
        while ($row = ccch_fetch($query)) {
            $row['id_rol']=trim($row['id_rol']);
            $row['rol']=trim($row['rol']);
            $id = $row['id_rol'];
            $name=$row['rol'];
            $rol_ER=$row['rol_alloweditrollo'];
            //error_log("ID2 " . $id2 . ", for name ", $name);
            if ($idrol == $id)
              {
                $html .= '<option value="' . $id . '" selected>' . $name . '</option>';
                $rolname_rn = $name;
                $idrename_rn = $id;
                $rol2delete = $id;
                $rol_alloweditrollo=$rol_ER;
              }
            else
              {
                $html .= '<option value="' . $id . '">' . $name . '</option> ';
              }
        }
        $html .= '</select>';
        if (isset($_POST["idrol"]) && ($idrol > 0)) $html .= '<button type="button" data-toggle="modal" data-target="#modalRename">Renombra</button>';
        if (isset($_POST["idrol"]) && ($idrol > 0)) $html .= '<button type="button" data-toggle="modal" data-target="#modalDelete">Borrar</button>';
        if (($_POST["idrol"]< 0) || !isset($_POST["idrol"])) $html .='<button type="button" data-toggle="modal" data-target="#modalCrea">Nuevo</button>';
        $html .='</div>
                  </div><br>';
        if (isset($_POST["idrol"]) && ($idrol > 0))
            {
                $html .= '<p><b>Usuarios Disponibles para asignar a este rol</b></p>
                 <div class="row">
                    <div class="col-xs-5">
                        <select name="userlist[]" id="multiselect" class="form-control" size="7" multiple="multiple">';
                            $query=ccch_query("select distinct id_wp_users from web_maestros_usuarios");
                            while ($row = ccch_fetch($query)) {
                                $row['id_wp_users']=trim($row['id_wp_users']);
                                $id = $row['id_wp_users'];
                                //error_log("id :" . $id);
                                $q = "select display_name, ID from wp_users where ID=" . $id;
                                $r = $wpdb->get_row($q);
                                $name=$r->display_name;
                                $id2=$r->ID;
                                $html .= '<option value="' . $id2 . '">' . $name . '</option>';
                            }
                        $html .='</select>
                    </div>

                    <div class="col-xs-2">
                        <button type="button" id="multiselect_rightAll" class="btn btn-block"> >> </button>
                        <button type="button" id="multiselect_rightSelected" class="btn btn-block"> > </button>
                        <button type="button" id="multiselect_leftSelected" class="btn btn-block"> < </button>
                        <button type="button" id="multiselect_leftAll" class="btn btn-block"> << </button>
                    </div>

                    <div class="col-xs-5">
                        <select name="assignedusers[]" id="multiselect_to" class="form-control" size="7" multiple="multiple">';
                            $query2=ccch_query("select distinct id_wp_users from web_maestros_usuarios where id_rol=" . $_POST["idrol"]);
                                while ($row = ccch_fetch($query2)) {
                                    $row['id_wp_users']=trim($row['id_wp_users']);
                                    $id = $row['id_wp_users'];
                                    //error_log("id :" . $id);
                                    $q = "select display_name, ID from wp_users where ID=" . $id;
                                    $r = $wpdb->get_row($q);
                                    $name=$r->display_name;
                                    $id2=$r->ID;
                                    $html .= '<option value="' . $id2 . '">' . $name . '</option>';
                                }
                        $html .= '</select>
                    </div>
                </div>';
            }
        if ($rol_alloweditrollo) $checked=' checked="checked"'; else $checked='';
        if (isset($_POST["idrol"]) && ($idrol > 0)) $html .= '<br><br><div class="container-fluid">
                    <div class="row">
                        <div class="col-xs-5 col-sm-5 col-lg-5"><label class="label">Permite EDITROLLO en la b&uacute;squeda al rol?</label></div>
                        <div class="col-xs-2 col-sm-2 col-lg-2"><input name="rol_alloweditrollo" ' . $checked . ' type="checkbox" value="1"></div>
                    </div>
                </div>';
        $html .='<script type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'#multiselect\').multiselect();
                    });
                </script>';
        if (isset($_POST["idrol"]) && ($idrol > 0)) $html .= '<div class="modal-footer">
                                    <button type="submit" name="Rols" value="Dismiss" class="btn btn-secondary">Cancelar</button>
                                    <button type="submit" name="Rols" value="Save" class="btn btn-success">Grabar</button>
                                </div>';
        $html .='</form>';
        // MODAL NEW
        $htmllast.='<form method="POST" name="Crea" id="Crea">';
        $htmllast.='<div class="modal fade" id="modalCrea" tabindex="-1" role="dialog" aria-labelledby="ModalLabelCrea" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="ModalLabelCrea"><strong>Crea un nuevo rol</strong></h5>
                                    </div>
                                    <div class="modal-body">
                                        <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Nombre del rol</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="new_rol" type="text" value="" required="required" ></div>
                                            </div>
                                        </div> <br>
                                        <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Permite EDITROLLO al rol?</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><input name="new_rol_alloweditrollo" type="checkbox" value="1"></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Basado en?</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><select id="basedrol" type="text" name="basedrol">
                                                    <option selected value=-1>Seleccione un Rol</option>';
                                                    $query=ccch_query("select * from web_maestros_rol");
                                                    while ($row = ccch_fetch($query)) {
                                                        $row['id_rol']=trim($row['id_rol']);
                                                        $row['rol']=trim($row['rol']);
                                                        $id = $row['id_rol'];
                                                        $name=$row['rol'];
                                                        $htmllast .= '<option value="' . $id . '">' . $name . '</option> ';
                                                          }
                                                $htmllast .= '</select></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                        <button type="submit" name="Rols" value="New" class="btn btn-success">Grabar</button>
                                    </div>

                                </div>
                            </div>
                    </div></form>';
        $htmllast2.='<form method="POST" name="Rename" id="Rename">';
        $htmllast2.='<div class="modal fade" id="modalRename" tabindex="-1" role="dialog" aria-labelledby="ModalLabelRename" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="ModalLabelDelete"><strong>Renombra un Rol</strong></h5>
                                </div>
                                <div class="modal-body">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Nuevo Nombre</label></div>
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;"  name="rename_rol" type="text" value="' . $rolname_rn . '" required="required" ></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <input type="hidden" name="oldname" value="' . $rolname_rn . '">
                                    <input type="hidden" name="id_rename" value="' . $idrol . '">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" name="Rols" value="Rename" class="btn btn-danger">Renombrar</button>
                                </div>

                            </div>
                        </div>
                </div></form>';
        $htmllast3.='<form method="POST" name="Delete" id="Delete">';
        $htmllast3.='<div class="modal fade" id="modalDelete" tabindex="-1" role="dialog" aria-labelledby="ModalLabelDelete" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="ModalLabelDelete"><strong>Selecciona Rol a Borrar</strong></h5>
                                </div>
                                <div class="modal-body">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Rol </label></div>
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label"> ' . $rolname_rn . '</label></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <input type="hidden" name="rol2delete" value="' . $rol2delete . '">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" name="Rols" value="Delete" class="btn btn-danger">Borrar</button>
                                </div>

                            </div>
                        </div>
                </div></form>';
        $html .= $htmllast;
        $html .= $htmllast2;
        $html .= $htmllast3;
        return $html;
    }

    function ccch_maestros_userlist()
    {
        global $wpdb;
        global $wp;
        $current_url = home_url(add_query_arg(array(),$wp->request));

        $html = '';

        $access=ccch_euser();
        $a=ccch_access_r($access);
        if ($a)  return $a;

        $htmllast='';
        $htmllast2='';
        $contrasenha='';
        $contrasenha2='';
        $plantas_all = ["Contirod","Upcast"];
        $con=ccch_connect();
        $iduser = getvar("iduser",'-1');
        $iddepto = '';
        //var_dump($_POST);

        $userinfo=get_userdata($iduser);
        $nombre=get_user_meta($iduser, 'first_name', True);
        $apellido=get_user_meta($iduser, 'last_name', True);
        $email = $userinfo->user_email;
        $displayname = $userinfo->display_name;

        //POST MODIFY
        if (isset($_POST["Modify"]) && ($_POST["Modify"] == 'Modify'))
        {
            if ($_POST['nombre'] != '') if (update_user_meta( $_POST["iduser"], 'first_name', $_POST["nombre"])) $nombre = $_POST["nombre"];

            if ($_POST['apellido'] != '') if (update_user_meta($_POST["iduser"], 'last_name', $_POST["apellido"])) $apellido = $_POST["apellido"];
            if (($_POST['nombre'] != '') && ($_POST['apellido'] != '')) if (!is_wp_error(wp_update_user( array('ID'=> $_POST["iduser"], 'user_nicename' => $_POST["nombre"] . " " . $_POST['apellido'])))) $nicename = $_POST["nombre"] . "-" . $_POST['apellido'];
            if (($_POST['nombre'] != '') && ($_POST['apellido'] != '')) if (!is_wp_error(wp_update_user( array('ID'=> $_POST["iduser"], 'display_name' => $_POST["nombre"] . " " . $_POST['apellido'])))) $nicename = $_POST["nombre"] . " " . $_POST['apellido'];
            if ($email != $_POST["e-mail"])
            {
                if (email_exists($_POST["e-mail"])) displayAlert("El correo electrónico ya existe, debe ingresar uno que no exista o modificar el actual: " . $_POST["e-mail"], "danger"); else $userupdate=wp_update_user( array('ID'=> $_POST["iduser"], 'user_email' => $_POST["e-mail"]));
            }
            if (($_POST['contrasenha'] != "") && ($_POST['contrasenha2'] !=""))
                if ($_POST['contrasenha2'] == $_POST['contrasenha'])
                {
                    wp_set_password( $_POST['contrasenha'], $_POST["iduser"] );
                    displayAlert("La contraseña se ha modificado correctamente", "success");
                }
                else
                {
                    displayAlert("Las  contraseñas no coinciden" . $_POST["e-mail"], "danger");
                }
            if ((isset($_POST["iddepto"])) && ($_POST["iddepto"] != ''))
            {
                $insert=ccch_query("DECLARE @id_wp_users INT = " . $_POST["iduser"] . "
                                    IF EXISTS (SELECT id_wp_users FROM web_maestros_planta WHERE id_wp_users=@id_wp_users)
                                    BEGIN
                                        update web_maestros_planta set iddepto='" . $_POST["iddepto"] . "' where  id_wp_users=" . $_POST["iduser"] . "
                                    END
                                    ELSE
                                    BEGIN
                                        INSERT INTO web_maestros_planta (id_wp_users, id_plantas, iddepto) VALUES (" . $_POST["iduser"] . ", 0,'" . $_POST["iddepto"] . "')
                                    END");
            } else
            {
                displayAlert("Debe estar selecccionada, al menos un Departamento" , "danger");
            }
            if (isset($_POST['user_alloweditrollo'])) $user_alloweditrollo = $_POST['user_alloweditrollo']; else $user_alloweditrollo = 0;
            $insert=ccch_query("update web_maestros_planta set user_alloweditrollo=" . $user_alloweditrollo . " where  id_wp_users=" . $_POST["iduser"]);
            if (isset($_POST["plantas"]))
            {
                $contirod_ok=True;
                $Upcast_ok=True;
                $plantas_set = 0;
                if (!in_array("Contirod", $_POST["plantas"])) $contirod_ok=False;
                if (!in_array("Upcast", $_POST["plantas"])) $Upcast_ok=False;
                if (($contirod_ok == True)  && ($Upcast_ok == False)) $plantas_set = 1;
                if (($contirod_ok == False)  && ($Upcast_ok == True)) $plantas_set = 2;
                //var_dump($plantas_set);
                //'INSERT INTO wp_uqox_session (wp_cookie,uqox_cookie,groupid) VALUES ("' . $wp_cookie . '","' . $uqox_cookie . '",' . $groupid . ') ON DUPLICATE KEY UPDATE wp_cookie="' . $wp_cookie . '",uqox_cookie="' . $uqox_cookie . '",groupid=' . $groupid);
                $insert=ccch_query("update web_maestros_planta set id_plantas=" . $plantas_set . " where  id_wp_users=" . $_POST["iduser"]);
            }
            else
            {
                 displayAlert("Debe estar selecccionada, al menos una planta" , "danger");
            }
            //echo '<META HTTP-EQUIV="Refresh" Content="1">';
        }
        // POST DELETE
        if ((isset($_POST["Modify"]) && ($_POST["Modify"] == 'Delete')))
        {
            if (wp_delete_user($_POST['iduser2del'],2) && ($_POST['iduser2del'] != 2))
            {
                $delete=ccch_query('DELETE FROM web_maestros_usuarios where id_wp_users=' . $_POST['iduser2del']);
                $delete=ccch_query('DELETE FROM web_maestros_planta where id_wp_users=' . $_POST['iduser2del']);
                displayAlert("Se ha podido eliminar el usuario.", "warning");
                //echo "<meta http-equiv='refresh' content='0'>";
            }
           else displayAlert("No se ha podido eliminar el usuario, puede contactar a soporte", "danger");

        }
        //POST NEW
        if ((isset($_POST["Modify"]) && ($_POST["Modify"] == 'New')))
        {
            if (($_POST["iduser_new"] != "") &&  ($_POST["email_new"] != "") &&  ($_POST["nombre_new"] != "") && ($_POST["apellido_new"] != "") && ($_POST["contrasenha_new"] != "") && ($_POST["contrasenha2_new"] != "") && ($_POST["iddepto_new"] != -1 ) && ($_POST["plantas_new"] != []))
            {
                //error_log("resultado de user exists: " . username_exists($_POST["iduser_new"]));
                if (username_exists($_POST["iduser_new"])) displayAlert("El nombre de usuario ya existe, debe ingresar uno que no exista o modificar el actual: " . $_POST["iduser_new"], "danger");
                else
                {
                    $iduser_new=$_POST["iduser_new"];
                    if (email_exists($_POST["email_new"])) displayAlert("El correo electrónico ya existe, debe ingresar uno que no exista o modificar el actual: " . $_POST["iduser_new"], "danger");
                    else
                    {
                        if (isset($_POST["plantas_new"]) && $plantas_new=[]) displayAlert("El usuario debe pertener, al menos, a una planta.", "danger");
                        else
                        {
                            if ($_POST["contrasenha2_new"] != $_POST["contrasenha_new"]) displayAlert("Las contraseñas no son iguales.", "danger");
                            else
                            {
                                //$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                                $user_id = wp_create_user( $_POST["iduser_new"], $_POST["contrasenha_new"], $_POST["email_new"] );
                                update_user_meta( $user_id, 'nickname', $_POST["iduser_new"]);
                                update_user_meta( $user_id, 'first_name', $_POST["nombre_new"]);
                                update_user_meta( $user_id, 'last_name', $_POST["apellido_new"]);
                                update_user_meta( $user_id, 'show_admin_bar_front', 'false');
                                wp_update_user( array('ID'=> $user_id, 'user_nicename' => $_POST["nombre_new"] . " " . $_POST['apellido_new']));
                                wp_update_user( array('ID'=> $user_id, 'display_name' => $_POST["nombre_new"] . " " . $_POST['apellido_new']));
                                $contirod_ok=True;
                                $Upcast_ok=True;
                                $plantas_set = 0;
                                if (isset($_POST["plantas_new"]) && !in_array("Contirod", $_POST["plantas_new"])) $contirod_ok=False;
                                if (isset($_POST["plantas_new"]) && !in_array("Upcast", $_POST["plantas_new"])) $Upcast_ok=False;
                                if (($contirod_ok == True)  && ($Upcast_ok == False)) $plantas_set = 1;
                                if (($contirod_ok == False)  && ($Upcast_ok == True)) $plantas_set = 2;
                                $insert=ccch_query("INSERT INTO web_maestros_usuarios (id_wp_users, id_rol) VALUES (" . $user_id . ", " . $_POST['idrol'] . ")");
                                if (isset($_POST['new_user_alloweditrollo'])) $user_alloweditrollo = $_POST['new_user_alloweditrollo']; else $user_alloweditrollo = 0;
                                $insert=ccch_query("INSERT INTO web_maestros_planta (id_wp_users, id_plantas, iddepto,user_alloweditrollo) VALUES (" . $user_id . ", " .  $plantas_set . ",'" . $_POST["iddepto_new"] . "', " . $user_alloweditrollo . ")");
                            }
                        }
                    }
                }
            }
            else displayAlert("Se deben llenar todos los campos para poder crear un usuario y seleccionar, al menos, una planta y un departamento", "warning");
        }

        $userinfo=get_userdata($iduser);
        $nombre=get_user_meta($iduser, 'first_name', True);
        $apellido=get_user_meta($iduser, 'last_name', True);
        $email = $userinfo->user_email;
        $displayname = $userinfo->display_name;
        if ($iduser != -1)
        {
            $query=ccch_query("select * from web_maestros_planta where id_wp_users=" . $iduser);
            while ($row = ccch_fetch($query)) {
                $row['id_plantas']=trim($row['id_plantas']);
                $row['iddepto']=trim($row['iddepto']);
                $row['user_alloweditrollo']=trim($row['user_alloweditrollo']);
                $id_plantas_array[] = $row['id_plantas'];
                $id_depto_array[] = $row['iddepto'];
                $id_rollo_array[] = $row['user_alloweditrollo'];
            }
            $id_plantas = min($id_plantas_array);
            $iddepto = min($id_depto_array);
            $user_alloweditrollo=max($id_rollo_array);
            switch ($id_plantas) {
                case "0":
                    $plantas=["Contirod","Upcast"];
                    break;
                case "1":
                    $plantas=["Contirod"];
                    break;
                case "2":
                    $plantas=["Upcast"];
                    break;
                default:
                   $plantas=["Contirod","Upcast"];
            }

            error_log("ID Depto: " . $iddepto . " and EDITROLLO =" . $user_alloweditrollo);
        }
        $query=ccch_query("select distinct id_wp_users from web_maestros_usuarios");
        $html.='<form method="POST" name="go" id="go">';
        $html .= '<div class="container-fluid">
                    <div class="row">
                        <div class="col-xs-1 col-sm-1 col-lg-3"" >Nombre de Usuario: </div>
                        <div class="col-xs-3 col-sm-3 col-lg-5"><select id="iduser" type="text" name="iduser" style="padding: 0px 0px;font-size:14px;" onchange="this.form.submit()">';
        if ($_POST["iduser"] == -1) $html .= '<option selected value=-1>Seleccione un Usuario</option>'; else $html .= '<option value=-1>Seleccione un Usuario</option>';
        while ($row = ccch_fetch($query)) {
            $row['id_wp_users']=trim($row['id_wp_users']);
            $id = $row['id_wp_users'];
            //error_log("id :" . $id);
            $q = "select display_name, ID from wp_users where ID=" . $id;
            $r = $wpdb->get_row($q);
            $name=$r->display_name;
            $id2=$r->ID;
            //error_log("ID2 " . $id2 . ", for name ", $name);
            if ($iduser == $id2)
              {
                $html .= '<option value="' . $id2 . '" selected>' . $name . '</option>';
              }
            else
              {
                $html .= '<option value="' . $id2 . '">' . $name . '</option> ';
              }
        }
        $html .= '</select>
                  <button type="button" data-toggle="modal" data-target="#modalCrea">Nuevo</button></div>
                  </div><br>';
        //var_dump($iddepto);
        if (isset($_POST["iduser"]) && ($iduser > 0))
        {
            $html .= '<div class="row">
                        <div class="col-xs-2 col-sm-2 col-lg-3"><label class="label">Nombre Sistema</label></div>
                        <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">' . $displayname . '</label></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Nombre</label></div>
                        <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="nombre" type="text" value="' . $nombre . '"></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Apellido</label></div>
                        <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="apellido" type="text" value="' . $apellido . '"></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">E-mail</label></div>
                        <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="e-mail" type="text" value="' . $email . '"></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Contraseña</label></div>
                        <div class="col-xs-3 col-sm-3 col-lg-5"><input  style="height: 25px;" name="contrasenha" type="password" value="' . $contrasenha . '"></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Confirmar Contraseña</label></div>
                        <div class="col-xs-3 col-sm-3 col-lg-5"><input  style="height: 25px;" name="contrasenha2" type="password" value="' . $contrasenha2 . '"></div>
                    </div>
                    <div class="row">
                                                <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Departamento</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><select id="iddepto" type="text" name="iddepto" style="padding: 0px 0px;font-size:14px;">';
                                                        $query=ccch_query("select * from Departamentos where Status=1");
                                                        if ($iddepto == '') $html .= '<option selected value=-1>Seleccione un Departamento</option>'; else $html .= '<option value=-1>Seleccione un Departamento</option>';
                                                        while ($row = ccch_fetch($query)) {
                                                            $row['CodDepto']=trim($row['CodDepto']);
                                                            $row['NomDepto']=trim($row['NomDepto']);
                                                            $iddepto2 = $row['CodDepto'];
                                                            $namedepto=$row['NomDepto'];
                                                            if ($iddepto == $iddepto2)
                                                              {
                                                                $html .= '<option value="' . $iddepto2 . '" selected>' . $namedepto . '</option>';
                                                              }
                                                            else
                                                              {
                                                                $html .= '<option value="' . $iddepto2 . '">' . $namedepto . '</option> ';
                                                              }
                                                        }
                    $html .='</select></div>
                    </div><br>
                    <div class="row">
                                                <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Plantas</label></div>';
                                                foreach($plantas_all as $value) {
                                                  if (in_array($value, $plantas)) $checked=' checked="checked"'; else $checked='';
                                                  $html.='<div class="form-check form-check-inline">
                                                    <input name="plantas[]" type="checkbox" value="' . $value . '"' . $checked . '>
                                                    <label class="form-check-label" for="inlineCheckbox1">' . $value . '</label>
                                                </div>';
                                                }
                    $html.='</div>';
                    if ($user_alloweditrollo) $checked=' checked="checked"'; else $checked='';
                    $html .= '<div class="row">
                                    <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Permitido para EDITROLLO?</label></div>
                                    <div class="col-xs-3 col-sm-3 col-lg-5"><input name="user_alloweditrollo" ' . $checked . ' type="checkbox" value="1"></div>
                                </div>';
                    $html.='<div class="modal-footer">
                                            <button type="button" data-toggle="modal" data-target="#modalDelete" class="btn btn-danger">Eliminar</button>
                                            <button type="submit" name="Modify" value="Modify" class="btn btn-success">Grabar</button>
                    </div>';
        }
        $html .= '</form>';
        // MODAL NEW
        $htmllast.='<form method="POST" name="Crea" id="Crea">';
        $htmllast.='<div class="modal fade" id="modalCrea" tabindex="-1" role="dialog" aria-labelledby="ModalLabelCrea" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="ModalLabelCrea"><strong>Crea un nuevo usuario</strong></h5>
                                    </div>
                                    <div class="modal-body">
                                        <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Nombre de Usuario</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="iduser_new" type="text" value="' . $iduser_new . '" required="required" ></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Correo Electr&oacute;nico</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="email_new" type="text" value="' . $email_new . '" required="required"></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Nombre</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"> <input style="height: 25px;" name="nombre_new" type="text" value="' . $nombre_new . '" required="required"></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Apellido</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="apellido_new" type="text" value="' . $apellido_new . '" required="required"></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Contraseña</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="contrasenha_new" type="password" value="' . $contrasenha_new . '" required="required"></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Confirma Contraseña</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="contrasenha2_new" type="password" value="' . $contrasenha2_new . '" required="required"></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Departamento</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><select id="iddepto_new" type="text" name="iddepto_new" style="padding: 0px 0px;font-size:14px;">';
                                                        $query=ccch_query("select * from Departamentos where Status=1");
                                                        while ($row = ccch_fetch($query)) {
                                                            $row['CodDepto']=trim($row['CodDepto']);
                                                            $row['NomDepto']=trim($row['NomDepto']);
                                                            $iddepto1 = $row['CodDepto'];
                                                            $namedepto1=$row['NomDepto'];
                                                            $htmllast .= '<option value="' . $iddepto1 . '">' . $namedepto1 . '</option> ';
                                                        }
                                                $htmllast .='</select></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Rol por Defecto</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><select id="idrol" type="text" name="idrol" style="padding: 0px 0px;font-size:14px;">';
                                                        $query=ccch_query("select * from web_maestros_rol");
                                                        while ($row = ccch_fetch($query)) {
                                                            $row['id_rol']=trim($row['id_rol']);
                                                            $row['rol']=trim($row['rol']);
                                                            $id = $row['id_rol'];
                                                            $name=$row['rol'];
                                                            $htmllast .= '<option value="' . $id . '">' . $name . '</option> ';
                                                        }
                                                $htmllast .='</select></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Permitido EDITROLLO?</label></div>
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><input name="new_user_alloweditrollo" type="checkbox" value="1"></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Plantas</label></div>
                                                <div class="form-check form-check-inline">
                                                    <input name="plantas_new[]" type="checkbox" value="Contirod" checked="checked>
                                                    <label class="form-check-label" for="inlineCheckbox1">Contirod</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input name="plantas_new[]" type="checkbox" value="Upcast" checked="checked>
                                                    <label class="form-check-label" for="inlineCheckbox1">Upcast</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                        <button type="submit" name="Modify" value="New" class="btn btn-success">Grabar</button>
                                    </div>

                                </div>
                            </div>
                    </div></form>';
        $htmllast2.='<form method="POST" name="Delete" id="Delete">';
        $htmllast2.='<div class="modal fade" id="modalDelete" tabindex="-1" role="dialog" aria-labelledby="ModalLabelDelete" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="ModalLabelDelete"><strong>Elimina un usuario</strong></h5>
                                </div>
                                <div class="modal-body">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">Nombre de Usuario a Borrar</label></div>
                                            <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="user_login_del" type="text" value="' . $userinfo->display_name . '" required="required" ></div>
                                        </div>
                                        <div class="row">
                                            <div class="alert alert-warmning role="warning">
                                                 <p>Esta acción no tiene recuperación y puede afectar la operación para el usuario ' . $userinfo->user_login . ', con el correo electrónico ' . $userinfo->user_email . '. Y nombre registrado a ' . $userinfo->display_name . '.<br><strong>Está seguro?</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <input type="hidden" name="iduser2del" value="' . $iduser . '">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" name="Modify" value="Delete" class="btn btn-danger">Borrar</button>
                                </div>

                            </div>
                        </div>
                </div></form>';
        $html .= $htmllast;
        $html .= $htmllast2;
        return $html;
    }

    function ccch_maestros_profile()
    {
        $contrasenha='';
        $contrasenha2='';
        $html = '';

        $access=ccch_euser();
        $a=ccch_access_r($access);
        if ($a)  return $a;

        // var_dump($_POST);
        if (isset($_POST["Modify"]) && ($_POST["Modify"] == 'Modify'))
            {
                if ($_POST['nombre'] != '') if (update_user_meta( $_POST["userid"], 'first_name', $_POST["nombre"])) $nombre = $_POST["nombre"];
                if ($_POST['apellido'] != '') if (update_user_meta($_POST["userid"], 'last_name', $_POST["apellido"])) $apellido = $_POST["apellido"];
                if (($_POST['nombre'] != '') && ($_POST['apellido'] != '')) if (!is_wp_error(wp_update_user( array('ID'=> $_POST["userid"], 'user_nicename' => $_POST["nombre"] . " " . $_POST['apellido'])))) $nicename = $_POST["nombre"] . "-" . $_POST['apellido'];
                if (($_POST['nombre'] != '') && ($_POST['apellido'] != '')) if (!is_wp_error(wp_update_user( array('ID'=> $_POST["userid"], 'display_name' => $_POST["nombre"] . " " . $_POST['apellido'])))) $nicename = $_POST["nombre"] . " " . $_POST['apellido'];
                if (($_POST['contrasenha'] != "") && ($_POST['contrasenha2'] !=""))
                    if (( $_POST['contrasenha2'] != '') && ($_POST['contrasenha2'] == $_POST['contrasenha']))
                    {
                        wp_set_password( $_POST['contrasenha'], $_POST["userid"] );
                        displayAlert("La contraseña se ha modificado correctamente", "success");
                    }
                    else
                    {
                        displayAlert("Las  contraseñas no coinciden" . $_POST["e-mail"], "danger");
                    }
            }
        if (is_user_logged_in())
        {
            $current_user = wp_get_current_user();
            $userid=$current_user->ID;
            $username=$current_user->user_login;
            $email=$current_user->user_email;
            $nombre=get_user_meta($userid, 'first_name', True);
            $apellido=get_user_meta($userid, 'last_name', True);
            $display = $current_user->display_name;
            $html.='<form method="POST" name="go" id="go">';
            $html .= '<div class="container-fluid">';
                $html .= '<div class="row">
                            <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Nombre de Usuario</label></div>
                            <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">' . $username . '</label></div>
                        </div>
                        <div class="row">
                        <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Nombre</label></div>
                        <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="nombre" type="text" value="' . $nombre . '"></div>
                    </div>
                        <div class="row">
                            <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Apellido</label></div>
                            <div class="col-xs-3 col-sm-3 col-lg-5"><input style="height: 25px;" name="apellido" type="text" value="' . $apellido . '"></div>
                        </div><br>
                        <div class="row">
                            <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Nombre en Sistema</label></div>
                            <div class="col-xs-3 col-sm-3 col-lg-5"><label class="label">' . $display . '</label></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">E-mail</label></div>
                            <div class="col-xs-3 col-sm-3 col-lg-5"><label style="height: 25px;" class="label">' . $email . '</label></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Contraseña</label></div>
                            <div class="col-xs-3 col-sm-3 col-lg-5"><input  style="height: 25px;" name="contrasenha" type="password" value="' . $contrasenha . '"></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-1 col-sm-1 col-lg-3"><label class="label">Confirmar Contraseña</label></div>
                            <div class="col-xs-3 col-sm-3 col-lg-5"><input  style="height: 25px;" name="contrasenha2" type="password" value="' . $contrasenha2 . '"></div>
                        </div>
                        <div class="modal-footer">
                            <input type="hidden" name="userid" value="' . $userid . '">
                            <button type="submit" name="Modify" value="Modify" class="btn btn-success">Grabar</button>
                        </div>';
            $html .= '</div></form>';
        } else
        {
            displayAlert("No está loggeado, es necesario estar loggeado para ver esta página", "danger");
        }
        return $html;
    }

    function ccch_showlogin($items, $args)
    {
        if (!is_user_logged_in())  return null;
        global $current_user;
        get_currentuserinfo();
        if (is_user_logged_in() && $args->theme_location == 'menu-1')
            {
                $items .= '<li>&nbsp;&nbsp;</li>';
                $items .= '<li><a style="padding:0px;" href="#" id="buscarelrollomenu"><i class="fa fa-question-circle-o" aria-hidden="true" data-tooltip="tooltip" data-placement="top"  title="Buscar Rollo">&nbsp;&nbsp;</i></a></li>';
                $items .= '<li><a style="padding-right:10px;" href="' . site_url() . '/cfg-usuarios-perfil/"><i class="fa fa-user" data-tooltip="tooltip" data-placement="top"  title="' . $current_user->display_name . '"></i></a></li>';
                $items .= '<li><a style="padding:0px;" href="' . wp_logout_url(get_permalink()) . '"><i class="fa fa-sign-out" aria-hidden="true" data-tooltip="tooltip" data-placement="top"  title="Salir"></i></a></li>';
            }
            elseif (!is_user_logged_in() && $args->theme_location == 'menu-1')
            {
                $items .= '<li><a href="' . wp_login_url(get_permalink()) . '"><i class="fa fa-sign-in" aria-hidden="true" data-tooltip="tooltip" data-placement="top"  title="Login"></i></a></li>';
            }
        return $items;
    }

    function ccch_maestros_permitlist()
    {
        global $wpdb;
        global $wp;
        $html = '';

        $access=ccch_euser();
        $a=ccch_access_r($access);
        if ($a)  return $a;

        $permitsperpage_bit = array();
        $permitsperpage_txt = array();
        $permits_check_all = ["Extra","Borrar","Escribir","Leer"];
        $permits_text_all = ["Control A","Control B","Control C"];
        $Permisos=ccch_user();
        $current_url = home_url(add_query_arg(array(),$wp->request));
        $html = '';
        $idrol = -1;
        $con=ccch_connect();
        if (isset($_POST["idrol"])) $idrol = $_POST["idrol"]; else $_POST["idrol"] = -1;
        if (isset($_GET["idrol"])) $idrol = $_GET["idrol"]; else $_GET["idrol"] = -1;
        if (isset($_POST["Permits"]) && ($_POST["Permits"] == 'Dismiss')) echo '<META HTTP-EQUIV="Refresh" Content="0; URL=' . $current_url . '?idrol=' . $_POST["idrol"] . '">';
        if (isset($_POST["Permits"]) && ($_POST["Permits"] == 'Save'))
        {
            $permitsperpage_bit_save=array();
            $permitsperpage_txt_save=array();
            $permitsperpage_bit_save=$_POST['permitsperpage_bit'];
            $permitsperpage_txt_save=$_POST['permitsperpage_txt'];
            $delete=ccch_query('DELETE FROM web_maestros_rolpost where id_rol=' . $_POST['idrol']);
            // UPdate permits
            $menu=wp_get_nav_menu_items('Menu2');
            foreach ($menu as $cls) {
                $q = "select post_title, ID from wp_posts where post_status='publish' and post_type='page' and ID=" . $cls->object_id;
                $r = $wpdb->get_results($q);
                foreach ($r as $cls2)
                {
                    $id2=$cls2->ID;
                    if (isset($permitsperpage_bit_save[$id2]))
                    {
                        if (in_array('Leer', $permitsperpage_bit_save[$id2])) $is_read=True; else $is_read=False;
                        if (in_array('Escribir', $permitsperpage_bit_save[$id2])) $is_write=True; else $is_write=False;
                        if (in_array('Borrar', $permitsperpage_bit_save[$id2])) $is_delete=True; else $is_delete=False;
                        if (in_array('Extra', $permitsperpage_bit_save[$id2])) $is_extra=True; else $is_extra=False;
                        if ($permitsperpage_txt_save[$id2]['Control A'] != '') $is_text1=$permitsperpage_txt_save[$id2]['Control A']; else $is_text1=NULL;
                        if ($permitsperpage_txt_save[$id2]['Control B'] != '') $is_text2=$permitsperpage_txt_save[$id2]['Control B']; else $is_text2=NULL;
                        if ($permitsperpage_txt_save[$id2]['Control C'] != '') $is_text3=$permitsperpage_txt_save[$id2]['Control C']; else $is_text3=NULL;
                        if (($is_write) || ($is_delete) || ($is_extra)) $is_read = True;
                        $insert=ccch_query("INSERT INTO web_maestros_rolpost (id_rol, id_wp_post, rol_read, rol_write, rol_delete, rol_extra, rol_t1, rol_t2, rol_t3)
                                            VALUES (" . $_POST['idrol'] . ", " . $id2 . ",'" . $is_read . "','" . $is_write . "','" . $is_delete . "','" . $is_extra . "','" . $is_text1 . "','" . $is_text2 . "','" . $is_text3 . "')");
                    }
                }
            }

        }
        if ($idrol != -1)
        {
            $query=ccch_query("select * from web_maestros_rolpost where id_rol=" . $idrol);
            while ($row = ccch_fetch($query)) {
                $row['idrol']=trim($row['idrol']);
                $row['id_wp_post']=trim($row['id_wp_post']);
                $row['rol_t1']=trim($row['rol_t1']);
                $row['rol_t2']=trim($row['rol_t2']);
                $row['rol_t3']=trim($row['rol_t3']);
                if ($row['rol_t1'] != '') $permitsperpage_txt[$row['id_wp_post']]['Control A'] = $row['rol_t1'];
                if ($row['rol_t2'] != '') $permitsperpage_txt[$row['id_wp_post']]['Control B'] = $row['rol_t2'];
                if ($row['rol_t3'] != '') $permitsperpage_txt[$row['id_wp_post']]['Control C'] = $row['rol_t3'];
                if ($row['rol_read']) $permitsperpage_bit[$row['id_wp_post']][] = "Leer";
                if ($row['rol_write']) $permitsperpage_bit[$row['id_wp_post']][] = "Escribir";
                if ($row['rol_delete']) $permitsperpage_bit[$row['id_wp_post']][] = "Borrar";
                if ($row['rol_extra']) $permitsperpage_bit[$row['id_wp_post']][] = "Extra";
            }
        }
        $query=ccch_query("select * from web_maestros_rol");
        $html .='<form method="POST" name="go" id="go">';
        $html .= '<div class="container-fluid">
                    <div class="row">
                        <div class="col-xs-4 col-sm-4 col-lg-4"><b>Nombre de Rol </b></div>
                        <div class="col-xs-3 col-sm-3 col-lg-5"><select id="idrol" type="text" name="idrol" onchange="this.form.submit()" style="padding: 0px 0px;font-size:14px;">';
        if ($_POST["idrol"] == -1) $html .= '<option selected value=-1>Seleccione un Rol</option>'; else $html .= '<option value=-1>Seleccione un Rol</option>';
        while ($row = ccch_fetch($query)) {
            $row['id_rol']=trim($row['id_rol']);
            $row['rol']=trim($row['rol']);
            $id = $row['id_rol'];
            $name=$row['rol'];
            if ($idrol == $id)
              {
                $html .= '<option value="' . $id . '" selected>' . $name . '</option>';
              }
            else
              {
                $html .= '<option value="' . $id . '">' . $name . '</option> ';
              }
        }
        $html .= '</select></div></div></div><br><br><br><br>';

        if (isset($_POST["idrol"]) && ($idrol > 0))
        {
            $menu=wp_get_nav_menu_items('Menu2');
            //var_dump($menu);
            $html .= '<div class="container-fluid" style="line-height: 1;">';
                $html .= '<div class="row">
                            <div class="col-xs-3 col-sm-3 col-lg-3"><label class="label"">Página</label></div>';
                            foreach($permits_check_all as $value) {
                            $html.='<div class="col-xs-1 col-sm-1 col-lg-1"> <label class="label" style="height: 18px;font-size: 14px;">' . $value . '</label></div>';
                            }
                            foreach($permits_text_all as $value) {
                            $html.='<div class="col-xs-1 col-sm-1 col-lg-1"><label class="label" style="height: 18px;font-size: 14px;">' . $value . '</label></div>';
                            }
                $html .= '</div><br>';
                        $whoisparent=array();
                        $whoisgrandparent=array();
                        foreach ($menu as $cls) {
                            //Who is parent & GrandParent..................
                            if (($cls->menu_item_parent == 0) && (!in_array($cls->ID, $whoisgrandparent))) $whoisgrandparent[] = $cls->ID;
                            if (($cls->menu_item_parent != 0) && (!in_array($cls->menu_item_parent, $whoisgrandparent)) && (!in_array($cls->menu_item_parent, $whoisparent))) $whoisparent[] = $cls->menu_item_parent;
                        }
                        foreach ($menu as $cls)
                        {
                        if ((!in_array($cls->ID, $whoisparent)) && (!in_array($cls->ID, $whoisgrandparent)))
                        {
                $html .= '<div class="row">';
                            if (in_array($cls->menu_item_parent, $whoisparent)) $padding='padding-left:30px;'; else $padding='padding-left:15px;';
                            $q = "select post_title, ID from wp_posts where post_status='publish' and post_type='page' and ID=" . $cls->object_id;
                            $r = $wpdb->get_results($q);
                            foreach ($r as $cls2)
                            {
                                $name3=$cls2->post_title;
                                $name2=explode(".",$name3);
                                $id2=$cls2->ID;
                                $name='';
                                if (count($name2)==1) $html .= '<div class="col-xs-3 col-sm-3 col-lg-3"><label class="label" style="' . $padding . 'font-family: Titillium Web,Sans;">'. $name2[0] . '</label></div>';
                                if (count($name2)==2) $html .= '<div class="col-xs-3 col-sm-3 col-lg-3"><label class="label" style="' . $padding . 'font-family: Titillium Web,Sans;">'. $name2[1] . '</label></div>';
                                if (count($name2)==3) $html .= '<div class="col-xs-3 col-sm-3 col-lg-3"><label class="label" style="' . $padding . 'font-family: Titillium Web,Sans;">'. $name2[2] . '</label></div>';
                                foreach($permits_check_all as $value) {
                                    $checked='';
                                    if (isset($permitsperpage_bit[$id2])) { if (in_array($value, $permitsperpage_bit[$id2])) $checked=' checked="checked"'; }
                                    $html.='<div class="col-xs-1 col-sm-1 col-lg-1"><input style="height: 18px;font-size: 14px;font-family: Titillium Web,Sans" name="permitsperpage_bit[' . $id2 . '][]" type="checkbox" value="' . $value . '"' . $checked . '></div>';
                                }
                                foreach($permits_text_all as $value) {
                                    $textvalue='';
                                    if (((isset($permitsperpage_bit)) && $permitsperpage_txt[$id2][$value] != '')) $textvalue=$permitsperpage_txt[$id2][$value];
                                    $html.='<div class="col-xs-1 col-sm-1 col-lg-1"><input style="height: 18px;width:60px;font-size: 14px;font-family: Titillium Web,Sans" name="permitsperpage_txt[' . $id2 . '][' . $value . ']" type="text" value="' . $textvalue . '" maxlength="3" size="3"></div>';
                                }
                            }
                $html .= '</div>';
                        }
                        if (in_array($cls->ID, $whoisparent))
                        {
                            $html .= '<div class="row">';
                            $q3= "select post_title, ID from wp_posts where post_status='publish' and post_type='page' and ID=" . $cls->object_id;
                            $r3 = $wpdb->get_results($q3);
                            foreach ($r3 as $cls3)
                            {
                                $name_menu_2=explode(".",$cls3->post_title);
                                $name='';
                                if (count($name_menu_2)==1) $html .= '<div class="col-xs-3 col-sm-3 col-lg-3"><label class="label" style="padding-left:15px;height: 18px;font-size: 14px;font-family: Titillium Web,Sans;"><strong>'. $name_menu_2[0] . '</strong></label></div>';
                                if (count($name_menu_2)==2) $html .= '<div class="col-xs-3 col-sm-3 col-lg-3"><label class="label" style="padding-left:15px;height: 18px;font-size: 14px;font-family: Titillium Web,Sans;"><strong>'. $name_menu_2[1] . '</strong></label></div>';
                                if (count($name_menu_2)==3) $html .= '<div class="col-xs-3 col-sm-3 col-lg-3"><label class="label" style="padding-left:15px;height: 18px;font-size: 14px;font-family: Titillium Web,Sans;"><strong>'. $name_menu_2[2] . '</strong></label></div>';
                            }
                            $html .= '</div>';
                        }
                        if  (in_array($cls->ID, $whoisgrandparent))
                        {
                            $html .= '<div class="row">';
                            $q3= "select post_title, ID from wp_posts where post_status='publish' and post_type='page' and ID=" . $cls->object_id;
                            $r3 = $wpdb->get_results($q3);
                            foreach ($r3 as $cls3)
                            {
                                $name_menu_2=explode(".",$cls3->post_title);
                                $name='';
                                if (count($name_menu_2)==1) $html .= '<div class="col-xs-3 col-sm-3 col-lg-3"><label class="label" style="height: 18px;font-size: 14px;font-family: Titillium Web,Sans;"><strong>'. $name_menu_2[0] . '</strong></label></div>';
                                if (count($name_menu_2)==2) $html .= '<div class="col-xs-3 col-sm-3 col-lg-3"><label class="label" style="height: 18px;font-size: 14px;font-family: Titillium Web,Sans;"><strong>'. $name_menu_2[1] . '</strong></label></div>';
                                if (count($name_menu_2)==3) $html .= '<div class="col-xs-3 col-sm-3 col-lg-3"><label class="label" style="height: 18px;font-size: 14px;font-family: Titillium Web,Sans;"><strong>'. $name_menu_2[2] . '</strong></label></div>';
                            }
                            $html .= '</div>';
                        }
                        }
            $html .='</div><br>';
            $html .='<div class="modal-footer">
                <button type="submit" name="Permits" value="Dismiss" class=" btn btn-secondary">Cancelar</button>
                <button type="submit" name="Permits" value="Save" class="btn btn-success">Grabar</button>
            </div>
        </form>';
        }
        return $html;
    }
?>