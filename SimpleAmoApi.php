<?php
/*
 * Простая PHP библиотека для работы с API AmoCRM
 * https://github.com/Colin990/SimpleAmoApi
 * Документация Amocrm: https://www.amocrm.com/developers/content/api/account/
 *
*/
class SimpleAmoApi {
	private $auth = [
		'subdomain' => 'https://vsfingroup.amocrm.ru',
		'client_id' => '86ee1fbc-5c12-43bc-bcc6-dfa3fc7f3b1a',
		'client_secret' => 'lxJsTjKsfqSUm7SYoCumN2tdepvanqpkB0M4pRu48O66Hja9PdZGX99UHXekq67A',
		'grant_type' => 'authorization_code',
		'code' => 'def5020063d79beb3c1cd5391a3c4f9bae10cdeb28ea13dec115868c21872bc1dc89901faaa20c02ca386fe53dfea4e2a2dd9bfd5f7b40e8a42b123ab9586273df660e81c7fd40f4ddbadea5e0f67cf47df565ec370ea8c0d90ab0b638c3f9470009a9225c3962539f340c36012ac45c34f5060b2f96acade935bd5a89226b078ee98a41437b6c9815c001dc5a53ae5d3086f2b6712024598e76830a29bb7fbb936685bdf7ffd7bcb1793e611419823cdb8c7242127a6afe515fc0a1ea1d7729e91a1887068a11fdc96ec0770ea626ffc1a3279688a8fed52f34cbfa47d30e48809794cad70f61dc88791102bf69679245578bbcd5f3b2d3c40903a1447d1e0e274dd6b67a1a0d00346a49960fde6af309ba127b40262b0193acf33fa8799ac5c9440c5919c0906816f50dec0e34e2a178e0dbb1ed2c1ca95cd4428872b627a14c609baf154e44553584ca77718409f3fcd3b14e28c46ba3eb506eba0216e51a630d9693580aa35df6c996ff14556f4bf9bb7bcda9ff2e6a0519ea832dfaaf6b79eb1c0148e116cd3df2d0d7ba27d313ab43327a06cb43dd70ecc23a058a19f750e46038ba7912eb1639275ff1fb58e0f77cd1f1b00a65a1e05afbaa4860abce255e6e0756ef887cdc8fca4a7b980b57b5073633c2f400111c5d',
		'redirect_uri' => 'https://investorschool.getcourse.ru',
	];

	private $tokens;

	function __construct() {
		if ( !$this->auth['client_secret'] ) self::ThrowError('Empty Auth Info');

		# Получаем access_token

		# Если есть файл с полученными ранее ключами
		if ( file_exists(__DIR__ . '/auth.json') ) {
			# Что бы не сверять время жизни ключа, просто генерируем новый
			$authInfo = json_decode( file_get_contents(__DIR__ . '/auth.json') );

			if ( !$authInfo->refresh_token ) self::ThrowError('Empty Refresh Token');

			# Обновляем access_token
			$newTokens = $this->refreshAccessToken( $authInfo->refresh_token );

			# Если новые токены есть, пишем их в файл
			if ( $newTokens->access_token ) {
				$newAuthJson = json_encode($newTokens);
				file_put_contents(__DIR__ . '/auth.json',$newAuthJson);

				$this->tokens = $newTokens;
			} else {
				self::ThrowError('Error. Cant generate new Access Token');
			}
		} else {

			# Генерируем первый раз ключи
			$newTokens = $this->getAccessToken();

			# Если новые токены есть, пишем их в файл
			if ( $newTokens->access_token ) {
				$newAuthJson = json_encode($newTokens);
				file_put_contents(__DIR__ . '/auth.json',$newAuthJson);

				$this->tokens = $newTokens;
			} else {
				self::ThrowError('Error. Cant generate Access Token');
			}
		}

		return;
	}

	function getAccessToken(){
		$data = [
			'client_id' => $this->auth['client_id'],
			'client_secret' => $this->auth['client_secret'],
			'grant_type' => 'authorization_code',
			'code' => $this->auth['code'],
			'redirect_uri' => $this->auth['redirect_uri'],
		];

		$newTokens = $this->SendRequest( 'oauth2/access_token', $data );

		return $newTokens;
	}

	function refreshAccessToken( $refreshToken ){
		$data = [
			'client_id' => $this->auth['client_id'],
			'client_secret' => $this->auth['client_secret'],
			'grant_type' => 'refresh_token',
			'refresh_token' => $refreshToken,
			'redirect_uri' => $this->auth['redirect_uri'],
		];

		$newTokens = $this->SendRequest( 'oauth2/access_token', $data );

		return $newTokens;
	}

	# https://www.amocrm.com/developers/content/api/account/
	function getAccount( $data = 'with=pipelines,groups,users,custom_fields' ){
		$method = 'api/v2/account';

		$response = $this->SendGETRequest( $method, $data );

		return $response;
	}

	# https://www.amocrm.com/developers/content/api/leads/
	function getLeads( $data = '' ){
		$method = 'api/v2/leads';

		$response = $this->SendGETRequest( $method, $data );
		$response = $this->formatResponse( $response );

		return $response;
	}

	# https://www.amocrm.com/developers/content/api/leads/
	function postLeads( $data = [] ){
		$method = 'api/v2/leads';

		$response = $this->SendRequest( $method, $data, 1 );
		$response = $this->formatResponse( $response );

		return $response;
	}

	# https://www.amocrm.com/developers/content/api/contacts/
	function getContacts( $data = '' ){
		$method = 'api/v2/contacts';

		$response = $this->SendGETRequest( $method, $data );
		$response = $this->formatResponse( $response );

		return $response;
	}

	# https://www.amocrm.com/developers/content/api/contacts/
	function postContacts( $data = [] ){
		$method = 'api/v2/contacts';

		$response = $this->SendRequest( $method, $data, 1 );
		$response = $this->formatResponse( $response );

		return $response;
	}

	# https://www.amocrm.com/developers/content/api/notes/
	function postNotes( $data = [] ){
		$method = 'api/v2/notes';

		$response = $this->SendRequest( $method, $data, 1 );
		$response = $this->formatResponse( $response );

		return $response;
	}

	function formatResponse( $response = [] ){

		if ( $response->update ) {
			return $response->update;
		}

		if ( $response->_embedded ) {
			return $response->_embedded->items;
		}

		return $response;
	}

	private function SendRequest( $method = '', $data = [], $sendToken = 0 ) {
		if ( !$method ) self::ThrowError('No Method in POST Request');

		$url = 'https://' . $this->auth['subdomain'] . '.amocrm.ru/' . $method;

		if (!$curld = curl_init()) {
			self::ThrowError('Curl Error');
		}

		if ( $sendToken ) {
			$header = [
				'Authorization: Bearer ' . $this->tokens->access_token
			];
		} else {
			$header = [
				'Content-Type: application/json'
			];
		}

		$verbose = fopen('php://temp', 'w+');

		curl_setopt($curld,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curld,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
		curl_setopt($curld,CURLOPT_URL, $url);
		if ( $header ) {
			curl_setopt($curld,CURLOPT_HTTPHEADER,$header);
		}
		curl_setopt($curld,CURLOPT_HEADER, false);
		if ( !empty($data) ) {
			curl_setopt($curld,CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curld,CURLOPT_POSTFIELDS, json_encode($data));
		}
		curl_setopt($curld,CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($curld,CURLOPT_SSL_VERIFYHOST, 2);

		$output = curl_exec($curld);
		curl_close($curld);

		if ( $output === FALSE ) {
			self::ThrowError("cUrl error: ".curl_errno($curld).' '.htmlspecialchars(curl_error($curld)));
		}

		rewind($verbose);
		$verboseLog = stream_get_contents($verbose);

		$response = json_decode($output);

		return $response;
	}

	private function SendGETRequest($method = '', $data = '' ){
		if ( !$method ) self::ThrowError('No Method in GET Request');

		$url = 'https://' . $this->auth['subdomain'] . '.amocrm.ru/' . $method;

		if ( $data ) :
			$url .= '?'. $data;
		endif;

		$opts = array(
			'http'=>array(
				'method'=>"GET",
				'header'=> 'Authorization: Bearer ' . $this->tokens->access_token
			)
		);

		$context = stream_context_create($opts);

		$response = file_get_contents($url, false, $context);

		return json_decode($response);
	}

	function ThrowError( $message ) {
		echo '<div style="margin: 30px 0; padding: 15px; text-align: center; color: #222; background: #ffdbdb;">';
		echo '<div style="margin: 0 0 10px; font-weight: 700;">Error:</div>';
		echo ( $message ) ? $message : 'Unknown problem';
		echo '</div>';
		die();
	}
}
