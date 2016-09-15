<?php  
	app::uses('AppController','Controller');
	app::uses('CakeEmail', 'Network/Email');
	
	class UsersController extends AppController{
		# Atributos
		# Helpers
		public $helpers = array('Html','Form','Time');
		# Components
		public $components = array('Paginator', 'Flash', 'RequestHandler');

		# Métodos
		# Autorización para acceso a módulos 
		public function isAuthorized($user){
			if($user['rol_id'] == 1){
				if(in_array($this->action, array('dashboard','login','logout','admin_index','admin_mi_cuenta','admin_buscar','admin_editar','admin_eliminar','admin_vendedores','admin_clientes','admin_nuevo_vendedor','admin_nuevo_cliente','admin_nuevo','admin_ver','admin_asignar_saldo','admin_asignar_distribuidores','admin_cambiar_contrasena','admin_correo_cuenta','admin_historial_compras','recuperar_contrasena', 'admin_carga_masiva'))){
					return true;
				}else{
					if($this->Auth->user('id')){
						$this->Flash->error(__('No puedes acceder a ese módulo de aplicación.'));
						$this->redirect($this->Auth->redirect());
					}
				}
			}
			if($user['rol_id'] == 2){
				if(in_array($this->action, array('dashboard', 'login', 'logout','admin_index','admin_buscar','admin_mi_cuenta','admin_editar','admin_clientes','admin_nuevo_cliente','admin_vendedores','admin_ver','admin_asignar_saldo','admin_asignar_distribuidores','admin_cambiar_contrasena','admin_correo_cuenta','admin_historial_compras', 'recuperar_contrasena', 'admin_carga_masiva'))){
					return true;
				}else{
					if($this->Auth->user('id')){
						$this->Flash->error(__('No puedes acceder a ese módulo de aplicación.'));
						$this->redirect($this->Auth->redirect());
					}
				}
			}
			if($user['rol_id'] == 3){
				if(in_array($this->action, array('login','logout','mi_cuenta','historial_compras','cambiar_contrasena', 'recuperar_contrasena','servicio_tecnico'))){
					return true;
				}else{
					if($this->Auth->user('id')){
						$this->Flash->error(__('No puedes acceder a ese módulo de aplicación.'));
						$this->redirect(array('controller'=>'productos', 'action'=>'index'));
					}
				}
			}	
			return parent::isAuthorized($user);
		}

		# Método BeforeFilter
		public function beforeFilter(){
			parent::beforeFilter();
			//$this->Auth->allow('add');
		}

		# Método de Login
		public function login(){
			$this->layout = 'inicio';
			if($this->Auth->user()){
				if($this->Auth->user('rol_id')==3){
					$this->redirect(array('controller' => 'productos', 'action' => 'index'));					
				}else{
					$this->redirect(array('controller' => 'users', 'action' => 'dashboard', 'admin'=>true));					
				}
			}
			if($this->request->is('post')){
				if($this->Auth->login()){
					$id = $this->Auth->user('id');
					$this->User->id = $id;
					$this->User->saveField('ultima_ip', $this->User->ip_sesion());
					$user = $this->User->find('first', array(
						'conditions'=>array(
							'User.id' => $id
						)
					));
					if($user['User']['rol_id']==1){
						return $this->redirect($this->Auth->redirect());
					}if($user['User']['rol_id']==2){
						return $this->redirect($this->Auth->redirect());					
					}if($user['User']['rol_id']==3){
						return $this->redirect(array('controller'=>'productos', 'action'=>'index'));					
					}
				}
				$this->Flash->error(__('Username y/o Contraseña son incorrectos.'));
				$this->set('titulo','Ingreso Usuarios');
			}
		}

		# Método de Recuperar Contraseña
		public function recuperar_contrasena(){
			if($this->request->is('post') || $this->request->is('put')){
				$usuario = $this->User->find('first', array(
					'conditions' => array(
						'User.rut' => $this->request->data['User']['rut'],
						'User.email' => $this->request->data['User']['email']
						)
					)
				);
				if(!$usuario){
					$this->Flash->success(__('Su nueva contraseña ha sido enviada a su dirección de correo electrónico.'));
					return $this->redirect(array('controller'=>'users', 'action'=>'login'));					
    			}

    			# Generamos la Nueva Password
    			$nueva_pass = AppController::generarPassword(4);

    			# Creamos el Email
	    		$email = new CakeEmail();
    			$email->template('email_cambio_pass');
    			$email->emailFormat('html');
    			$email->viewVars(
    				array(
    					'rut_completo' => $usuario['User']['rut_completo'],
    					'rut' => $usuario['User']['rut'],
    					'username' => $usuario['User']['email'],
    					'nombre_completo' => $usuario['User']['nombre_completo'],
    					'empresa' => $usuario['User']['empresa'],
    					'rol_id' => $usuario['User']['rol_id'],
    					'nueva_pass' => $nueva_pass,
    				)
    			);
   				$email->from(array('distribuidores@miretail.cl'=>'Distribuidores Miretail'));
    			$email->to(array($usuario['User']['email'] => $usuario['User']['empresa']));
    			$email->subject('[Miretail] Recuperación de Contraseña');   			
    			# Enviamos el email.
    			# Al enviar debemos cambiar el estado del usuario.
    			if(
    				$this->User->usuarioCambiarContrasena($usuario['User']['id'], $nueva_pass) &&
    				$email->send()
    			){
    				$this->Flash->success(__('Su nueva contraseña ha sido enviada a su dirección de correo electrónico.'));
					return $this->redirect(array('controller'=>'users', 'action'=>'login'));
    			}else{
    				$this->Flash->success(__('Su nueva contraseña ha sido enviada a su dirección de correo electrónico.'));
					return $this->redirect(array('controller'=>'users', 'action'=>'login'));   				
    			}
			}else{
				$this->Flash->success(__('Su nueva contraseña ha sido enviada a su dirección de correo electrónico.'));
				return $this->redirect(array('controller'=>'users', 'action'=>'login'));				
			}
		}

		# Método de Logout
		public function logout(){
			$this->Flash->success(__('Has salido con éxito.'));
			return $this->redirect($this->Auth->logout());
		}

		# Dashboard
		public function dashboard(){			
			$this->set('titulo','Dashboard');		
		}	

		#------------------------------------------------------------------------------------------------------------------#
		#											Administración                                                         #
		#------------------------------------------------------------------------------------------------------------------#

		# Método Index
		public function admin_index(){
			$this->User->recursive = 0;
			$paginador = $this->Paginator->paginate();
			$this->set('usuarios', $paginador);
			$this->set('titulo','Listar Usuarios');
		}

		# Método Listar Vendedores
		public function admin_vendedores(){
			$this->User->recursive = 0;
			$this->Paginator->settings = array(
    		    'conditions' => array('User.rol_id' => 2)
    		);
			$paginador = $this->Paginator->paginate();
			$this->set('usuarios', $paginador);
			$this->set('titulo','Listar Vendedores');
		}

		# Método Listar Clientes
		public function admin_clientes(){
			$this->User->recursive = 0;
			$this->Paginator->settings = array(
    		    'conditions' => array('User.rol_id' => 3)
    		);
			$paginador = $this->Paginator->paginate();
			$this->set('usuarios', $paginador);
			$this->set('titulo','Listar Clientes');
		}

		# Método Nuevo Usuario
		public function admin_nuevo(){
			if($this->request->is('post') || $this->request->is('put')){
				# Calculamos Dígito Verificador
				$this->request->data['User']['dv'] = $this->User->digitoVerificador($this->request->data['User']['rut']);
				# Generamos el Username
				$this->request->data['User']['username'] = $this->User->generarUsername($this->request->data['User']['rut'],$this->request->data['User']['dv']);
				# Generamos la Contraseña
				$this->request->data['User']['password'] = $this->request->data['User']['rut'];
				# Creamos el usuario				
				$this->User->create();
				# Guardamos el registro				
				if($this->User->save($this->request->data)){
					$this->Flash->success(__('El Usuario ha sido creado exitosamente.'));
					return $this->redirect(array('action' => 'admin_index'));
				}
				else{
					$this->Flash->error(__('El Usuario no pudo ser creado. Por favor, intentar nuevamente.'));
				}
			}
			# Comunas
			$comunas = $this->User->Comuna->find('list', array('order' => array('nombre')));
			# Roles
			$roles = $this->User->Rol->find('list', array('fields' => array('id','nombre')));
			# Llevamos las variables a la vista
			$this->set(compact('comunas', 'roles'));
			$this->set('titulo','Agregar Nuevo Usuario');
			$this->set('errors', $this->User->validationErrors);
		}

		# Método Nuevo Vendedor
		public function admin_nuevo_vendedor(){
			if($this->request->is('post') || $this->request->is('put')){
				# Calculamos Dígito Verificador
				$this->request->data['User']['dv'] = $this->User->digitoVerificador($this->request->data['User']['rut']);
				# Generamos el Username
				$this->request->data['User']['username'] = $this->User->generarUsername($this->request->data['User']['rut'],$this->request->data['User']['dv']);
				# Generamos la Contraseña
				$this->request->data['User']['password'] = $this->request->data['User']['rut'];
				# Creamos el usuario				
				$this->User->create();
				# Guardamos el registro
				if ($this->User->save($this->request->data)){
					$this->Flash->success(__('El Usuario ha sido creado exitosamente.'));
					return $this->redirect(array('action' => 'admin_ver', $this->User->getLastInsertID()));
				}else {
					$this->Flash->error(__('El Usuario no pudo ser guardado. Por favor, intentar nuevamente.'));
				}
			}
			# Comunas
			$comunas = $this->User->Comuna->find('list', array('order' => array('nombre')));
			# Roles
			$roles = $this->User->Rol->find('list', array('fields' => array('id','nombre')));
			# Llevamos las variables a la vista
			$this->set(compact('comunas', 'roles'));
			$this->set('titulo','Agregar Nuevo Vendedor');
		}

		# Método Nuevo Cliente
		public function admin_nuevo_cliente(){
			if($this->request->is('post') || $this->request->is('put')){
				# Calculamos Dígito Verificador
				$this->request->data['User']['dv'] = $this->User->digitoVerificador($this->request->data['User']['rut']);
				# Generamos el Username
				$this->request->data['User']['username'] = $this->User->generarUsername($this->request->data['User']['rut'],$this->request->data['User']['dv']);
				# Generamos la Contraseña
				$this->request->data['User']['password'] = $this->request->data['User']['rut'];
				# Creamos el usuario
				$this->User->create();
				# Guardamos el registro
				if ($this->User->save($this->request->data)){
					$this->Flash->success(__('El Usuario ha sido creado exitosamente.'));
					return $this->redirect(array('action' => 'admin_ver', $this->User->getLastInsertID()));
				}else {
					$this->Flash->error(__('El Usuario no pudo ser guardado. Por favor, intentar nuevamente.'));
				}
			}
			# Comunas
			$comunas = $this->User->Comuna->find('list', array('order' => array('nombre')));
			# Roles
			$roles = $this->User->Rol->find('list', array('fields' => array('id','nombre')));
			# Llevamos las variables a la vista
			$this->set(compact('comunas', 'roles'));
			$this->set('titulo','Agregar Nuevo Cliente');
		}

		# Método Nuevo Cliente MultiRut
		public function admin_nuevo_cliente_multirut($id){
    		if(!$id) {
    	    	throw new NotFoundException(__('Usuario NO Existente'));
    		}
    		$usuario = $this->User->findById($id);
    		if(!$usuario){
    	    	throw new NotFoundException(__('Usuario NO Existente'));
    		}			
			if($this->request->is('post') || $this->request->is('put')){
				# Calculamos Dígito Verificador
				$this->request->data['User']['dv'] = $this->User->digitoVerificador($this->request->data['User']['rut']);
				# Generamos el Username
				$this->request->data['User']['username'] = $this->User->generarUsername($this->request->data['User']['rut'],$this->request->data['User']['dv']);
				# Generamos la Contraseña
				$this->request->data['User']['password'] = $this->request->data['User']['rut'];
				# Creamos el usuario
				$this->User->create();
				# Guardamos el registro
				if ($this->User->save($this->request->data)){
					$this->Flash->success(__('El Usuario ha sido creado exitosamente.'));
					return $this->redirect(array('action' => 'admin_ver', $this->User->getLastInsertID()));
				}else {
					$this->Flash->error(__('El Usuario no pudo ser guardado. Por favor, intentar nuevamente.'));
				}
			}
			# Comunas
			$comunas = $this->User->Comuna->find('list', array('order' => array('nombre')));
			# Roles
			$roles = $this->User->Rol->find('list', array('fields' => array('id','nombre')));
			# Llevamos las variables a la vista
			$this->set(compact('comunas', 'roles'));
			$this->set('usuario',$usuario);
			$this->set('titulo','Agregar Nuevo Cliente Multirut');
		}

		# Método Buscar
		public function admin_buscar(){
			$this->User->recursive = 1;
			$busqueda = null;
        	        if(empty($this->request->query['s'])){
    	    	                $this->Flash->error(__('No has ingresado ningún parámetro de búsqueda.'));
    	        return $this->redirect(array('action' => 'admin_index'));        		
        	}
        	$busqueda = $this->request->query['s'];
			$this->paginate = array('conditions' => array(
				'OR' => array(
						'User.rut_completo LIKE ' => '%'.$busqueda.'%',
						'User.empresa LIKE ' => '%'.$busqueda.'%',
						'User.nombres LIKE ' => '%'.$busqueda.'%',
						'User.ap_pat LIKE ' => '%'.$busqueda.'%',
						'User.ap_mat LIKE ' => '%'.$busqueda.'%'
				),
			),
			);
			$contador = $this->User->find('count');
			$this->set('contador', $contador);
			$this->set('busqueda', $busqueda);
			$this->set('usuarios', $this->paginate());
			$this->set('titulo', 'Resultados de Búsqueda: "'.$busqueda.'"');
		}

		# Método Ver
		public function admin_ver($id = null){
			if (!$this->User->exists($id)) {
				throw new NotFoundException(__('Usuario NO Existente'));
			}
			$options = array('conditions' => array('User.' . $this->User->primaryKey => $id));
			$compraTotal = $this->User->obtenerMontoComprado($id);
			$this->set('compraTotal', $compraTotal);
			$this->set('usuario', $this->User->find('first', $options));
			$this->set('titulo','Ver Usuario');
		}


		# Editar Usuarios
		public function admin_editar($id = null) {
    		if(!$id) {
    	    	throw new NotFoundException(__('Usuario NO Existente'));
    		}
    		$usuario = $this->User->findById($id);
    		if(!$usuario){
    	    	throw new NotFoundException(__('Usuario NO Existente'));
    		}
    		if($this->User->usuarioEditar($this->Auth->user('rol_id'),$usuario)){
    	    	$this->Flash->error(__('No tienes los permisos suficientes para editar este usuario.')); 
    	    	return $this->redirect(array('action' => 'admin_ver', $usuario['User']['id']));    	    	   			
    		}
    		if ($this->request->is(array('post','put'))){
    	    	$this->User->id = $id;
    	    	if ($this->User->save($this->request->data)) {
    	    	    $this->Flash->success(__('El Usuario ha sido guardado exitosamente.'));
    	        	return $this->redirect(array('action' => 'admin_ver', $id));
    	    	}else{
					$this->Flash->error(__('El Usuario no pudo ser guardado. Por favor, intentar nuevamente.'));
				}
    		}
    		if(!$this->request->data) {
	        	$this->request->data = $usuario;
	    	}
			$comunas = $this->User->Comuna->find('list', array('fields' => array('id', 'nombre'), 'order' => array('nombre asc')));
			$roles = $this->User->Rol->find('list', array('fields' => array('id','nombre')));
			// Llevamos las Variables a la Vista.
			$this->set(compact('comunas', 'roles', 'usuario'));
			$this->set('titulo','Editar Usuario Existente');
		}

		# Método Eliminar Usuario
		public function admin_eliminar($id = null){
			$this->User->id = $id;
			if (!$this->User->exists()) {
				throw new NotFoundException(__('Usuario NO Existente'));
			}
			$this->request->allowMethod('post', 'delete');
			if ($this->User->delete()) {
				$this->Flash->success(__('El Usuario ha sido eliminado.'));
			} else {
				$this->Flash->error(__('El Usuario no pudo ser eliminado. Por favor, intentar nuevamente.'));
			}
			return $this->redirect(array('action' => 'index'));
		}
	
		# Asignar Saldo
		public function admin_asignar_saldo($id = null) {
    		# Si el RUT no existe, se provoca un error.
    		if(!$id) {
    	    	throw new NotFoundException(__('Usuario NO Existente'));
    		}
    		$usuario = $this->User->findById($id);
    		# Si el Usuario está vacio.
    		if(!$usuario){
    	    	throw new NotFoundException(__('Usuario NO Existente'));
    		}
    		# Si el usuario es admin o manager de ventas no puede acceder al módulo.
    		if($this->User->usuarioAdminManager($usuario)){
    			$this->Flash->error(__('Administradores y Managers de Venta no pueden tener saldo asignado.'));
				$this->redirect(array('action'=>'ver', $usuario['User']['id']));
    		}
    		# Si la petición es de tipo POST o PUT permite entrar al bloque.
    		if ($this->request->is(array('post', 'put'))) {
    			# Tomamos el ID para ingresar datos.
    	    	$this->User->id = $id;    	    	
    	    	# Guardamos los datos de usuarios.
    	    	if ($this->User->save($this->request->data)) {
    	    	    $this->Flash->success(__('El Usuario ha sido guardado exitosamente.'));
    	        	return $this->redirect(array('action' => 'admin_ver',$usuario['User']['id']));
    	    	}
    	    	$this->Flash->error(__('El Usuario no pudo ser guardado. Por favor, intentar nuevamente.'));
    		}
    		if (!$this->request->data) {
	        	$this->request->data = $usuario;
	    	}
	    	# Tomamos las variables y las llevamos a la vista.
			$this->set(compact('usuario'));
			$this->set('titulo','Editar Usuario Existente');
		}

		# Asignar Saldo
		public function admin_asignar_distribuidores($id = null) {   		
    		# Vemos que exista un RUT
    		if(!$id) {
    	    	throw new NotFoundException(__('Usuario NO Existente'));
    		}
    		$usuario = $this->User->findById($id);		
    		# Si el usuario no existe lanza un error
    		if(!$usuario){
    	    	throw new NotFoundException(__('Usuario NO Existente'));
    		}
    		# Si el usuario es Admin o Manager no permite el ingreso.
    		if($this->User->usuarioAdminManager($usuario)){
    			$this->Flash->error(__('Administradores y Managers de Venta no pueden tener distribuidores asignados.'));
				$this->redirect(array('action'=>'admin_ver', $usuario['User']['id']));
    		}    		
    		# Carga de Modelos Adicionales
	    	$this->loadModel('Marca');
	    	$this->loadModel('DistribuidoresUser');			
	    	# Se realiza un bind del modelo
			$this->Marca->bindModel(array('hasMany' => array('DistribuidoresUser' => array('className' => 'DistribuidoresUser','conditions' => array('DistribuidoresUser.user_id' => $usuario['User']['id'])))),false); 	    	
    		# Contador de Marcas para el tema distribuidores.
   	    	$contador = $this->Marca->find('count', array('recursive'=>-1));
			# Contador de Distribuidores			
			$contadorDist = $this->User->contarDistribuidores($id);
			# Contador de Marcas
   	    	$contadorMarcas = $this->Marca->find('count');
			# Obtenemos los Distribuidores			
			$distribuidoresusers = $this->User->obtenerDistribuidores($id);
			# Si la petición es tipo POST o PUT entra al bloque
    		if ($this->request->is(array('post', 'put'))) {
    			$contador1 = 0;
    			if($contadorDist == $contadorMarcas):
    				foreach($this->request->data['DistribuidoresUser'] as $distr):
    					$this->DistribuidoresUser->id = $distr['id'];
    					if ($this->DistribuidoresUser->saveAll($distr)){
    						$contador1++;
    					}
    				endforeach;
    			else:
    				foreach($this->request->data['DistribuidoresUser'] as $distr):
    					if ($this->DistribuidoresUser->saveAll($distr)){
    						$contador1++;
    					}
    				endforeach;
    			endif;
    	    	if($contador == $contador1){
    	    	    $this->Flash->success(__('La lista de distribuidores ha sido asignada exitosamente al Usuario.'));
    	        	return $this->redirect(array('action' => 'admin_ver',$usuario['User']['id']));    	    		
    	    	}else{
    	    		$this->Flash->error(__('La lista de distribuidores no pudo ser asignada. Intentelo nuevamente.'));
    			}
    	    }

    	    # Listamos las Marcas
   	    	$marcas = $this->Marca->find('all', array('recursive'=>2));

    	    # Listamos los Distribuidores
	    	$distribuidores = $this->User->Distribuidor->find('list');
	    	
	    	# Llevamos las variables a la vista
	    	$this->set(compact('usuario','marcas','distribuidores','distribuidoresusers','contadorDist'));
			$this->set('titulo','Asignar Existente');
		}

		# Módulo Administración Mi Cuenta
		public function admin_mi_cuenta(){
			$usuario = $this->User->findById($this->Auth->user('id'));
			if ($this->request->is(array('post', 'put'))) {
    	    	$this->User->id = $this->request->data['User']['id'];
    	    	if ($this->User->save($this->request->data)) {
    	    	    $this->Flash->success(__('El Usuario ha sido guardado exitosamente.'));
    	        	return $this->redirect(array('action' => 'admin_mi_cuenta'));
    	    	}
    	    	$this->Flash->error(__('El Usuario no pudo ser guardado. Por favor, intentar nuevamente.'));
    		}
    		if (!$this->request->data) {
	        	$this->request->data = $usuario;
	    	}
			$this->set('usuario', $usuario);
			$this->set('titulo','Mi Cuenta');
		}

		# Módulo Administración Cambiar Contraseña
		public function admin_cambiar_contrasena(){
			if ($this->request->is(array('post', 'put'))) {
				$this->User->id = $this->request->data['User']['id'];
				if ($this->User->save($this->request->data)) {
    	    	    $this->Flash->success(__('El Usuario ha cambiado su contraseña exitosamente.'));
    	        	return $this->redirect(array('action' => 'admin_mi_cuenta'));
    	    	}else{
    	    		$this->Flash->error(__('El Usuario no cambiar su contraseña. Por favor, intentar nuevamente.'));
    	        	return $this->redirect(array('action' => 'admin_mi_cuenta'));
				}
			}
		}

		# Módulo Administración Correo de Bienvenida
		public function admin_correo_cuenta($id = NULL){
			# Vemos que exista un RUT
    		if(!$id){
    	    	throw new NotFoundException(__('Usuario NO Existente'));
    		}
    		$usuario = $this->User->findById($id);		
    		# Si el usuario no existe lanza un error
    		if(!$usuario){
    	    	throw new NotFoundException(__('Usuario NO Existente'));
    		}
    		# Si el usuario es cliente y no posee distribuidores asignados
    		if( 
    			$usuario['User']['rol_id'] == 3 &&
    			$this->User->contarDistribuidores($id) == 0
    		){
    			$this->Flash->error(__('Antes de enviar el correo de bienvenida, debes asignar distribuidores.'));
    		    return $this->redirect(array('action' => 'admin_ver', $id));     			
    		}
    		# Si la petición es vía post
    		if ($this->request->is(array('post', 'put'))){    		
    			# Creamos el Email
	    		$email = new CakeEmail();
    			$email->template('email_bienvenida');
    			$email->emailFormat('html');
    			$email->viewVars(
    				array(
    					'rut_completo' => $usuario['User']['rut_completo'],
    					'rut' => $usuario['User']['rut'],
    					'email' => $usuario['User']['email'],
    					'nombre_completo' => $usuario['User']['nombre_completo'],
    					'empresa' => $usuario['User']['empresa'],
    					'rol_id' => $usuario['User']['rol_id'],
    				)
    			);
   				$email->from(array('distribuidores@miretail.cl'=>'Distribuidores Miretail'));
    			$email->to(array($usuario['User']['email'] => $usuario['User']['empresa']));
    			$email->subject('[Miretail] ¡Accede ahora mismo a tu Portal de Distribuidores!');   			
    			# Enviamos el email.
    			# Al enviar debemos cambiar el estado del usuario.
    			if(
    				$email->send() && 
    				$this->User->usuarioCambiarEstadoBienvenida($usuario['User']['id'])
    			){
    				$this->Flash->success(__('El correo de bienvenida ha sido enviado exitosamente.'));
    		        return $this->redirect(array('action' => 'admin_ver',$id));
    			}else{
    				$this->Flash->error(__('El correo de bienvenida no pudo ser enviado exitosamente, intentelo nuevamente.'));
    		        return $this->redirect(array('action' => 'admin_ver',$id));    				
    			}
    		}	
		}

		# Método Historial de Compras
		public function admin_historial_compras($id = NULL){
			if(!$id){
				throw new NotFoundException(__('Usuario NO Existente'));				
			}
			# Tomamos datos de usuario
			$usuario = $this->User->findById($id);
			# Si el usuario esta vacío, arroja error.
			if(!$usuario){
				throw new NotFoundException(__('Usuario NO Existente'));
			}
			# Si el usuario no es cliente, arroja error.
			if($usuario['User']['rol_id'] != 3){
    			$this->Flash->error(__('Sólo los clientes pueden tener historial de compras.'));
    		    return $this->redirect(array('action' => 'admin_ver', $id)); 				
			}
			# Cargamos Modelo Compra
			$this->loadModel('Compra');
			# Paginamos los resultados.
			$this->paginate = array('conditions' => array('Compra.user_id' => $id),'order'=>array('Compra.id' => 'desc'));
			# Llevamos las variables a la vista.
			$this->set('compras', $this->paginate('Compra'));
			$this->set('usuario', $usuario);
			$this->set('titulo','Historial de Compra - '.$usuario['User']['empresa']);
		}


		# Método Carga Masiva
		public function admin_carga_masiva(){
			if($this->request->is('post')){
				if($this->request->data['Carga']['csv']['error'] == 0){
					# Nombre de Archivo Cargado
					$tmpname = $this->request->data["Carga"]["csv"]["tmp_name"];
					
					# Opciones del CSV (Configurar posteriormente)
					$delim = ';';		# Delimitador de Archivo.
					$enc = ''; 			# Caracter para Encerrar.
					$line = "\n";		# Caracter para salto de línea.
					$i = 0;				# Iterador.

					# Cargamos los Modelos
					$this->loadModel("Rol");
					$this->loadModel("Distribuidor");
					$this->loadModel("Comuna");
					$this->loadModel("Marca");
					$this->loadModel("DistribuidoresUser");

					$marcas = $this->Marca->find('all');

					# Declaramos el Arreglo de User para almacenamiento de información
					$this->request->data['User'] = array();
					#--------------------------------------------------------------------------------
					# 	PARTE 1 : Obtención de Data
					#--------------------------------------------------------------------------------
					foreach(str_getcsv(file_get_contents($tmpname),$line) as $row ){					
						if($i == 0){
							$campos = str_getcsv($row, $delim, $enc);
							if(
								(in_array("email", $campos) == false && in_array("Email", $campos) == false) OR
								(in_array("rut", $campos)   == false && in_array("RUT", $campos)   == false) OR
								(in_array("rol", $campos)   == false && in_array("Rol", $campos)   == false) OR
								(in_array("empresa", $campos)   == false && in_array("Empresa", $campos)   == false) OR								
								(in_array("direccion", $campos)   == false && in_array("Direccion", $campos)   == false) OR
								(in_array("comuna", $campos)   == false && in_array("Comuna", $campos)   == false) OR
								(in_array("ciudad", $campos)   == false && in_array("Ciudad", $campos)   == false)
							){
								$this->Flash->error(__('El archivo de carga no posee todos los campos obligatorios. Por favor checkear e intentar nuevamente.'));		
								return $this->redirect(array('action' => 'admin_carga_masiva'));						
							}
						}else{
							# Tomamos los datos de la Línea.
							$csv = str_getcsv($row,$delim,$enc);
							# Inicializamos k en 0.							
							$k=0;
							for($j=0; $j<sizeof($campos); $j++){
								# Consultamos el campo Comuna.
								if($campos[$j]=='comuna'){
									# Consultamos la Comuna por nombre.
									$comuna = $this->Comuna->find('first', array(
										'conditions' => array('Comuna.nombre = ' => utf8_encode(strtoupper($csv[$j]))),
										'fields' => array('Comuna.id'),
										'recursive' => 0
									));
									# Tomamos el ID de la Comuna
									$comuna_id = $comuna['Comuna']['id'];
									$this->request->data['User'][$i-1]['comuna_id'] = $comuna_id;

								}elseif($campos[$j]=='rol'){
									# Consultamos la Rol por nombre.
									$rol = $this->Rol->find('first', array(
										'conditions' => array('Rol.nombre' => ucfirst(strtolower($csv[$j]))),
										'fields' => array('Rol.id'),
										'recursive' => -1
									));
									# Tomamos el ID del campo Rol
									$rol_id = $rol['Rol']['id'];
									$this->request->data['User'][$i-1]['rol_id'] = $rol_id;
								}elseif($campos[$j]=='email'){
									$this->request->data['User'][$i-1][$campos[$j]] = utf8_encode(strtolower($csv[$j]));									
								}elseif($campos[$j]=='distribuidor'){
									$distribuidor = $this->Distribuidor->find('first',array(
										'conditions' => array('Distribuidor.slug'=>strtolower($csv[$j])),
										'fields' => array('Distribuidor.id'),
										'recursive' => -1
									));
									# Tomamos el ID del Modelo Distribuidor
									$distribuidor_id = $distribuidor['Distribuidor']['id'];
									$this->request->data['User'][$i-1]['DistribuidoresUser'] = array();
									# Generamos la Información de Distribuidores Users.
									for($k=0; $k<sizeof($marcas); $k++){
										# Inicializamos el ID de usuario como nulo para meterlo.
										$this->request->data['User'][$i-1]['DistribuidoresUser'][$k]['user_id'] = NULL;
										$this->request->data['User'][$i-1]['DistribuidoresUser'][$k]['marca_id'] = $marcas[$k]['Marca']['id'];
										$this->request->data['User'][$i-1]['DistribuidoresUser'][$k]['distribuidor_id'] = $distribuidor_id;

									}

								}elseif($campos[$j]=='rut'){
									$this->request->data['User'][$i-1][$campos[$j]] = $csv[$j];
									$this->request->data['User'][$i-1]['username'] = $this->User->generarUsername($csv[$j], $this->User->digitoVerificador($csv[$j]));
									$this->request->data['User'][$i-1]['password'] = $csv[$j];								
								}else{
									$this->request->data['User'][$i-1][$campos[$j]] = utf8_encode(ucwords(strtolower($csv[$j])));
								}
							}							 							
						}
						$i++;
					}
					#-------------------------------------------------------------------------------
					# 	PARTE 2 : Carga de Data
					#-------------------------------------------------------------------------------
					$usuarios['User'] = array();								// Creamos array de producto.
					$usuarios['User'] = $this->request->data['User'];		// Llevamos los datos al array. 

					#echo '<pre>';
					#print_r($usuarios);
					#echo '</pre>';

					
					$log_file = "";

					foreach($usuarios['User'] as $usuario){
						$usuario_existe = $this->User->findByEmail($usuario['email']);
						if(!$usuario_existe){
							$this->User->create();
							if($this->User->save($usuario)){
								$log_file .= "Empresa : ".$usuario['empresa']." - Agregada correctamente...<br/>";
								$usuario_id = $this->User->getLastInsertID();
								
								foreach($usuario['DistribuidoresUser'] as $distrUser){
									$this->DistribuidoresUser->create();
									$distrUser['user_id'] = $usuario_id;	
									if($this->DistribuidoresUser->save($distrUser)){
										//$log_file .= "Marca ID #".$distrUser['marca_id']." : Asignada Correctamente a Usuario - (".$usuario['empresa'].") ...<br/>";
									}else{
										//$log_file .= "Marca ID #".$distrUser['marca_id']." : No pudo ser Asignada Correctamente a Usuario - (".$usuario['empresa'].") ...<br/>";
									}
								}
							}else{
								$log_file .= "<strong>Empresa : ".$usuario['empresa']." - No pudo ser agregada correctamente...</strong><br/>";								
							}
						}else{
							$log_file .= "<strong>Empresa : ".$usuario['empresa']." - Ya se encuentra en nuestra base de datos...</strong><br/>";
						}
					}
					$this->set('log',$log_file);
					$this->Flash->success(__('Los Usuarios han sido cargados satisfactoriamente.'));	
					
				}else{
					$this->Flash->error(__('El archivo de carga no ha sido subido. Por favor, intentar nuevamente.'));
				}
			}
			$this->set('titulo','Carga Masiva de Usuarios');			
		}




	
		#------------------------------------------------------------------------------------------------------------------#
		#											    Clientes                                                           #
		#------------------------------------------------------------------------------------------------------------------#

		# Módulo Mi Cuenta
		public function mi_cuenta(){
			# Seleccionamos Layout
			$this->layout = 'default-mar';
			# Tomamos los datos del usuario
			$usuario = $this->User->findById($this->Auth->user('id'));
			# Si la petición es tipo POST o PUT, ejecutamos el bloque.			
			if ($this->request->is(array('post', 'put'))) {
				# Tomamos el usuario a editar.
    	    	$this->User->id = $this->request->data['User']['id'];
    	    	# Guardamos los datos de usuario.
    	    	if ($this->User->save($this->request->data)) {
    	    	    $this->Flash->success(__('El Usuario ha sido guardado exitosamente.'));
    	        	return $this->redirect(array('action' => 'mi_cuenta'));
    	    	}
    	    	$this->Flash->error(__('El Usuario no pudo ser guardado. Por favor, intentar nuevamente.'));
    		}
    		# Si no hay datos en la petición, se le asigna la variable usuario.
    		if (!$this->request->data) {
	        	$this->request->data = $usuario;
	    	}
			$compraTotal = $this->User->obtenerMontoComprado($usuario['User']['id']);
			$this->set('compraTotal', $compraTotal);
			$this->set('usuario', $usuario);
			$this->set('titulo','Mi Cuenta');
		}

		# Módulo Cambiar Contraseña
		public function cambiar_contrasena(){
			# Seleccionamos Layout
			$this->layout = 'default-mar';
			# Si la petición es tipo POST o PUT, ejecutamos el bloque.
			if ($this->request->is(array('post', 'put'))) {
				# Tomamos el usuario a editar.
				$this->User->id = $this->request->data['User']['id'];
    	    	# Guardamos los datos de usuario.
    	    	if ($this->User->save($this->request->data)) {
    	    	    $this->Flash->success(__('El Usuario ha cambiado su contraseña exitosamente.'));
    	        	return $this->redirect(array('action' => 'mi_cuenta'));
    	    	}else{
    	    		$this->Flash->error(__('El Usuario no pudo ser guardado. Por favor, intentar nuevamente.'));
    	        	return $this->redirect(array('action' => 'mi_cuenta'));
				}
			}
		}

		# Modulo Historial de Compras
		public function historial_compras(){
			# Seleccionamos Layout
			$this->layout = 'default-mar';
			# Tomamos datos de usuario
			$usuario = $this->User->findById($this->Auth->user('id'));
			# Si el usuario esta vacío, arroja error.
			if(!$usuario){
				throw new NotFoundException(__('Usuario NO Existente'));
			}
			# Cargamos Modelo Compra
			$this->loadModel('Compra');
			# Paginamos los resultados.
			$this->paginate = array('conditions' => array('Compra.user_id' => $this->Auth->user('id')),'order'=>array('Compra.id' => 'desc'));
			# Llevamos las variables a la vista.
			$this->set('compras', $this->paginate('Compra'));
			$this->set('titulo','Mi Historial de Compra');
		}

		# Formulario de Servicio Técnico
		public function servicio_tecnico(){
			if($this->request->is(array('post', 'put'))){
				$this->loadModel('Comuna');
				$this->request->data['User']['fecha_solicitud'] = date('d-m-Y - H:m:s');
				$comuna = $this->Comuna->findById($this->request->data['User']['comuna_id']);
				//echo '<pre>';
				//print_r($this->request->data);
				//echo '</pre>';
				# Creamos el Email
	    		$email = new CakeEmail();
    			$email->template('email_servicio_tecnico');
    			$email->emailFormat('html');
    			$email->viewVars(array('datos' => $this->request->data['User'],'comuna'=>$comuna));
   			$email->from(array('distribuidores@miretail.cl'=>'Distribuidores Miretail'));
    			$email->to(
    				array(
    					'servicios2@servimetsa.cl' => 'Servimet S.A.',
    					'servicios7@servimetsa.cl' => 'Servimet S.A.',
    					'callcenter@servimetsa.cl' => 'Servimet S.A.',
    					'ffigueroa@mimet.cl' => 'Felipe Figueroa',
    					'mhernandez@miretail.cl' => 'Marco Hernandez',
    					'ventas@miretail.cl' => 'Hugo Vásquez',
    					'asistentegerencia@miretail.cl' => 'Carolina Vergara Herrera',
    					'fernando.saavedra@fable.cl' => 'Fernando Saavedra'
    					$this->Auth->user('email') => $this->Auth->user('empresa')
    				)
    			);
    			$email->subject('[Distribuidores Miretail] Formulario Servicio Técnico'); 
    			if($email->send()){
					$this->Flash->success(__('El Mensaje ha sido enviado exitosamente.'));
					return $this->redirect($this->referer());
				}else{
					$this->Flash->error(__('El Mensaje no pudo ser enviado exitosamente.'));
					return $this->redirect($this->referer());
				}

			}			
		}

	}
?>