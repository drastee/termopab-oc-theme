<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Tool;

/**
 * Serves GLB/3D files from the upload table by code (for product view_360).
 * Route: extension/termopab/tool/glb.serve?code=...
 */
class Glb extends \Opencart\System\Engine\Controller {

	/**
	 * Serve GLB file for embedding in 3D viewer (no download, correct MIME).
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
		$mime = ($ext === 'glb') ? 'model/gltf-binary' : 'model/gltf+json';

		$this->response->addHeader('Content-Type: ' . $mime);
		$this->response->addHeader('Content-Length: ' . filesize($file));
		$this->response->addHeader('Cache-Control: public, max-age=86400');
		$this->response->setOutput(file_get_contents($file));
	}
}
