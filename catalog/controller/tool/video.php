<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Tool;

/**
 * Serves video files from the upload table by code (for product main_video).
 * Route: extension/termopab/tool/video.serve?code=...
 */
class Video extends \Opencart\System\Engine\Controller {

	/**
	 * Serve video file for embedding (no download, correct MIME).
	 */
	public function serve(): void {
		$code = isset($this->request->get['code']) ? trim((string)$this->request->get['code']) : '';
		if ($code === '') {
			$this->response->addHeader('HTTP/1.1 404 Not Found');
			return;
		}

		$this->load->model('tool/upload');
		$upload_info = $this->model_tool_upload->getUploadByCode($code);
		if (!$upload_info || empty($upload_info['filename'])) {
			$this->response->addHeader('HTTP/1.1 404 Not Found');
			return;
		}

		$file = DIR_UPLOAD . $upload_info['filename'];
		if (!is_file($file)) {
			$this->response->addHeader('HTTP/1.1 404 Not Found');
			return;
		}

		$ext = strtolower(pathinfo($upload_info['name'], PATHINFO_EXTENSION));
		$mime_map = [
			'mp4'  => 'video/mp4',
			'webm' => 'video/webm',
			'ogg'  => 'video/ogg',
			'mov'  => 'video/quicktime',
		];
		$mime = $mime_map[$ext] ?? 'video/mp4';

		$this->response->addHeader('Content-Type: ' . $mime);
		$this->response->addHeader('Content-Length: ' . filesize($file));
		$this->response->addHeader('Cache-Control: public, max-age=86400');
		$this->response->setOutput(file_get_contents($file));
	}
}
