<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function terms(): View
    {
        return view('pages.legal', [
            'title' => 'Termos de Uso',
            'updatedAt' => '12/10/2026',
            'intro' => 'Estes Termos de Uso regem o uso do FinFamília. Ao criar uma conta ou utilizar o serviço, você concorda com as condições abaixo.',
            'sections' => [
                ['heading' => '1. Aceitação dos termos', 'body' => 'Ao criar uma conta, você declara ter lido e concordado com estes Termos de Uso e com a Política de Privacidade (LGPD). Se não concordar, não utilize o serviço.'],
                ['heading' => '2. Descrição do serviço', 'body' => 'O FinFamília é um sistema de gestão financeira familiar para registro e organização de receitas, despesas, contas, cartões e investimentos. O serviço é destinado exclusivamente ao controle e à organização das informações inseridas pelos próprios usuários e seus familiares.'],
                ['heading' => '3. Conta e cadastro', 'body' => 'Para utilizar o serviço é necessário criar uma conta com informações verdadeiras. Você é responsável por manter a confidencialidade da senha e por todas as atividades realizadas na sua conta. O administrador da conta familiar pode convidar membros e gerenciar os acessos.'],
                ['heading' => '4. Uso adequado', 'body' => 'Você concorda em não utilizar o serviço para fins ilícitos, para burlar regras fiscais, para acessar dados de terceiros sem autorização ou para prejudicar a operação do sistema.'],
                ['heading' => '5. Natureza dos registros', 'body' => 'O FinFamília é uma ferramenta de registro e organização. Não realiza pagamentos, transações bancárias ou investimentos em seu nome. Consulte sempre as instituições financeiras para confirmação de valores e saldos.'],
                ['heading' => '6. Propriedade intelectual', 'body' => 'O nome, a marca e a interface do FinFamília pertencem ao seu mantenedor. Os dados inseridos por você continuam sendo seus e são tratados conforme a Política de Privacidade.'],
                ['heading' => '7. Limitação de responsabilidade', 'body' => 'O serviço é fornecido "no estado em que se encontra", sem garantia de disponibilidade ininterrupta. O mantenedor não se responsabiliza por decisões financeiras tomadas com base nos dados informados pelo usuário.'],
                ['heading' => '8. Encerramento da conta', 'body' => 'O administrador pode encerrar o cadastro da família a qualquer momento, o que apaga os registros e revoga o acesso de todos os membros. Você também pode solicitar a exclusão dos dados conforme a LGPD.'],
                ['heading' => '9. Alterações dos termos', 'body' => 'Estes Termos podem ser atualizados. A versão vigente estará sempre disponível nesta página e a data de atualização será indicada no topo.'],
                ['heading' => '10. Contato', 'body' => 'Dúvidas sobre estes Termos ou sobre o tratamento dos seus dados podem ser encaminhadas pelo e-mail suporte@finfamilia.com.br.'],
            ],
        ]);
    }

    public function privacy(): View
    {
        return view('pages.legal', [
            'title' => 'Política de Privacidade',
            'updatedAt' => '12/10/2026',
            'intro' => 'Esta Política de Privacidade descreve como o FinFamília coleta, usa, armazena e protege seus dados pessoais, em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018 — LGPD).',
            'sections' => [
                ['heading' => '1. Controlador', 'body' => 'O FinFamília é o controlador dos dados pessoais tratados no serviço. As solicitações sobre privacidade podem ser encaminhadas para suporte@finfamilia.com.br.'],
                ['heading' => '2. Dados coletados', 'body' => 'Coletamos os dados que você informa ao criar e usar a conta: nome, e-mail, telefone, data de nascimento e os registros financeiros que você mesmo cadastra (receitas, despesas, contas, cartões, investimentos e membros da família).'],
                ['heading' => '3. Finalidades do tratamento', 'body' => 'Os dados são utilizados exclusivamente para operar o serviço, gerar relatórios e gráficos, permitir o controle financeiro familiar e prestar suporte. Não utilizamos seus dados para publicidade de terceiros.'],
                ['heading' => '4. Base legal', 'body' => 'O tratamento se apoia nas bases legais da LGPD: execução do contrato (art. 7º, V) e legítimo interesse do usuário em utilizar o serviço (art. 7º, IX). Dados sensíveis não são tratados.'],
                ['heading' => '5. Compartilhamento', 'body' => 'Não vendemos nem compartilhamos seus dados com terceiros para fins comerciais. Os dados ficam restritos à sua família e aos provedores de infraestrutura necessários para o funcionamento (hospedagem, banco de dados e e-mail).'],
                ['heading' => '6. Armazenamento e segurança', 'body' => 'Seus dados são armazenados em servidores protegidos, com criptografia em trânsito (HTTPS), senhas com hash e controles de acesso por perfil (administrador, co-administrador, dependente e júnior).'],
                ['heading' => '7. Seus direitos (art. 18 da LGPD)', 'body' => 'Você pode solicitar a confirmação da existência de tratamento, o acesso aos dados, a correção, a anonimização ou o bloqueio de dados desnecessários, a portabilidade e a eliminação dos dados tratados com o seu consentimento. Para exercer seus direitos, envie um e-mail para suporte@finfamilia.com.br.'],
                ['heading' => '8. Período de retenção', 'body' => 'Os dados são mantidos enquanto a conta estiver ativa. Ao encerrar o cadastro (somente o administrador pode), os registros são apagados, conforme o procedimento descrito nos Termos de Uso.'],
                ['heading' => '9. Encarregado (DPO)', 'body' => 'Para assuntos relacionados à proteção de dados, fale com o Encarregado (DPO) pelo e-mail dpo@finfamilia.com.br.'],
                ['heading' => '10. Alterações desta política', 'body' => 'Esta política pode ser atualizada. A versão vigente estará sempre disponível nesta página, com a data de atualização indicada no topo.'],
            ],
        ]);
    }
}
