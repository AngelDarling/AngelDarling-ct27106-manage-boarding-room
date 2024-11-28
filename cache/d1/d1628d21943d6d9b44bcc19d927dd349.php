<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* errors/404.twig */
class __TwigTemplate_2208f090d70e352957d45571463a4004 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 2
        echo "
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Document</title>
    <script src=\"https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js\"
        integrity=\"sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r\"
        crossorigin=\"anonymous\"></script>
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js\"
        integrity=\"sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy\"
        crossorigin=\"anonymous\"></script>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css\" rel=\"stylesheet\"
        integrity=\"sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH\" crossorigin=\"anonymous\">
    <script type=\"text/javascript\" src=\"https://cdn.jsdelivr.net/npm/toastify-js\"></script>
    <link rel=\"stylesheet\" type=\"text/css\" href=\"https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css\">
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css\">
<style>
    main {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100vh;
        background-color: #f8f9fa; /* màu nền nhẹ nhàng */
        color: #333; /* màu chữ */
    }
</style>
</head>
";
        // line 31
        echo "<main>
    <div class=\"d-flex justify-content-center\" style=\"height: 100vh;\">
        <img src=\"/assets/img/404.gif\" alt=\"Error Image\" class=\"img-fluid\" />
    </div>
    <div class=\"my-1 d-flex justify-content-center\">
        ";
        // line 36
        if ((($context["role"] ?? null) == "ADMIN")) {
            // line 37
            echo "            <a href=\"/admin/dashboard\" class=\"btn btn-primary btn-lg me-1\">
                <i class=\"fa fa-home\"></i> Về trang quản trị
            </a>
        ";
        } elseif ((        // line 40
($context["role"] ?? null) == "USER")) {
            // line 41
            echo "            <a href=\"/\" class=\"btn btn-primary btn-lg me-1\">
                <i class=\"fa fa-home\"></i> Về trang chủ
            </a>
        ";
        } else {
            // line 45
            echo "            <p>PageNotFound</p>
        ";
        }
        // line 47
        echo "
    </div>
</main>
    ";
    }

    public function getTemplateName()
    {
        return "errors/404.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  93 => 47,  89 => 45,  83 => 41,  81 => 40,  76 => 37,  74 => 36,  67 => 31,  37 => 2,);
    }

    public function getSourceContext()
    {
        return new Source("", "errors/404.twig", "C:\\xampp\\htdocs\\ct27106-manage-boarding-room\\app\\Views\\errors\\404.twig");
    }
}
