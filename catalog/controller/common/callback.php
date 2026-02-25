<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Common;

class Callback extends \Opencart\System\Engine\Controller {
	public function save(): void {
		$this->load->language('extension/termopab/module/callback_form');

		$json = [];

		$name = trim((string)($this->request->post['name'] ?? ''));
		$phone = trim((string)($this->request->post['phone'] ?? ''));

		if ($name === '' || $phone === '') {
			$json['error'] = 'Заполните имя и телефон.';
		}

		if (!$json) {
			$callback_request_id = 0;
			try {
				$this->load->model('extension/termopab/callback_request');
				$callback_request_id = $this->model_extension_termopab_callback_request->addCallbackRequest([
					'name' => $name,
					'phone' => $phone,
					'status' => 0,
					'comment' => '',
					'ip' => (string)($this->request->server['REMOTE_ADDR'] ?? ''),
					'user_agent' => (string)($this->request->server['HTTP_USER_AGENT'] ?? ''),
				]);
			} catch (\Throwable $e) {
				$this->log->write('termopab callback.save db error: ' . $e->getMessage());
				$json['error'] = 'Не удалось сохранить заявку. Попробуйте позже.';
			}

			if (empty($json['error'])) {
				$json['success'] = 'Заявка отправлена. Мы скоро свяжемся с вами.';
				$json['callback_request_id'] = $callback_request_id;

				$mail_engine = (string)$this->config->get('config_mail_engine');
				if ($mail_engine !== '') {
					try {
						$store_name = (string)$this->config->get('config_name');
						$to = (string)$this->config->get('config_email');

						$from = (string)$this->config->get('config_email');
						if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
							$from = (string)$this->config->get('config_mail_smtp_username');
						}

						$subject = $store_name . ': Заявка на обратный звонок';
						$message = "ID: {$callback_request_id}\nИмя: {$name}\nТелефон: {$phone}\n";

						$mail = new \Opencart\System\Library\Mail($mail_engine);
						$mail->parameter = $this->config->get('config_mail_parameter');
						$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
						$mail->smtp_username = $this->config->get('config_mail_smtp_username');
						$mail->smtp_password = html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
						$mail->smtp_port = (int)$this->config->get('config_mail_smtp_port');
						$mail->smtp_timeout = (int)$this->config->get('config_mail_smtp_timeout');

						$mail->setTo($to);
						$mail->setFrom($from);
						$mail->setSender($store_name);
						$mail->setSubject($subject);
						$mail->setText($message);
						$mail->send();
					} catch (\Throwable $e) {
						$this->log->write('termopab callback.save mail error: engine=' . $mail_engine . '; to=' . (string)$this->config->get('config_email') . '; error=' . $e->getMessage());
					}
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
