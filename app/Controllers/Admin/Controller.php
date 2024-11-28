<?php
namespace App\Controllers\Admin;
use App\Models\contract;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
class Controller
{
    protected $view;

    public function __construct()
    {
        $loader = new FilesystemLoader(ROOTER . 'app/views');
        $this->view = new Environment($loader);
        $this->view->addGlobal('csrf_token', $_SESSION['csrf_token']);
        // Đăng ký hàm tùy chỉnh cho Twig
        $this->view->addFunction(new TwigFunction('url_with_params', 'url_with_params'));
    }

    public function sendPage($page, array $data = [])
    {
        $data['appUrl'] = URL_APP();
        $contract = new contract(pdo: PDO());
        $data['pendingcontracts'] = $contract->getPendingcontracts();
        exit($this->view->render($page . '.twig', $data));
    }


    protected function saveFormValues(array $data, array $except = [])
    {
        $form = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, $except, true)) {
                $form[$key] = $value;
            }
        }
        $_SESSION['form'] = $form;
    }

    protected function getSavedFormValues()
    {
        return $_SESSION['form'] ?? [];
    }

    public function sendNotFound()
    {
        http_response_code(404);
        exit($this->view->render('errors/404.twig'));
    }

}
