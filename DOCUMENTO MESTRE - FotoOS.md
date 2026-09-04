# DOCUMENTO MESTRE

## Especificação Funcional — Plataforma PWA de Relatórios Fotográficos de Serviços em Campo

**Versão:** 1.1  
**Data:** 02/09/2026  
**Status:** Especificação inicial do MVP (Stack Técnica Definida)  
**Modelo:** Aplicação Web + PWA  
**Escopo inicial:** Operação para uma única empresa  
**Arquitetura:** Preparada para futura evolução para SaaS Multi-Tenant com RBAC e Multi-Pipeline

---

# 1. VISÃO GERAL DO PROJETO

A plataforma tem como objetivo permitir a criação rápida e padronizada de **relatórios fotográficos de serviços realizados em campo**, utilizando smartphones, tablets ou computadores.

O sistema deverá permitir que o responsável pelo serviço:

1. Informe o número da OS;
2. Selecione ou informe a unidade;
3. Informe um ou mais setores;
4. Informe os técnicos envolvidos;
5. Registre o histórico do serviço;
6. Tire fotografias diretamente pelo dispositivo;
7. Capture automaticamente a geolocalização;
8. Obtenha o endereço correspondente à localização;
9. Registre a data e hora oficial utilizando o servidor;
10. Adicione observações individuais às fotografias;
11. Organize as fotografias;
12. Finalize o relatório;
13. Gere o documento em PDF;
14. Gere versão para impressão;
15. Gere versão em imagem;
16. Compartilhe o relatório pelo WhatsApp.

A plataforma utilizará **Laravel** no backend, com geração de PDFs otimizada via **DomPDF**, armazenamento de mídias em **disco local** e geolocalização processada gratuitamente via **OpenStreetMap**. O sistema deverá priorizar **simplicidade, velocidade, rastreabilidade e validade documental das evidências fotográficas**.

---

# 2. OBJETIVOS

## 2.1 Objetivo principal

Substituir processos manuais de elaboração de relatórios fotográficos por uma plataforma digital capaz de produzir documentos padronizados e com evidências de local, data, horário, serviço, OS, unidade, setor, técnicos envolvidos, fotografias e observações.

## 2.2 Objetivos secundários

- Reduzir o tempo de elaboração dos relatórios;
- Evitar preenchimento repetitivo;
- Padronizar documentos;
- Facilitar consulta posterior e compartilhamento;
- Melhorar a rastreabilidade das atividades;
- Reduzir possibilidade de adulteração das informações;
- Permitir utilização em campo através de PWA;
- Funcionar mesmo em ambientes com conexão instável;
- Preparar a aplicação para futura comercialização como SaaS.

---

# 3. ESCOPO DO MVP

O MVP deverá contemplar:

- Configuração da empresa e logomarca;
- Configuração do cabeçalho;
- Cadastro progressivo de unidades e setores;
- Criação de relatório com número de OS digitado manualmente;
- Seleção de unidade e múltiplos setores;
- Técnicos envolvidos e Histórico;
- Captura de fotografias com marca d'água;
- Geolocalização convertida em endereço;
- Data/hora obtida do servidor;
- Observação individual e ordenação das fotografias;
- Finalização do relatório e geração de PDF;
- Compartilhamento via WhatsApp;
- Consulta de relatórios;
- PWA com funcionamento offline parcial e sincronização;
- Configuração administrativa.

---

# 4. FORA DO ESCOPO INICIAL

Não fazem parte do MVP:

- Cadastro individual e login de técnicos;
- Controle de ponto e Gestão completa de usuários;
- CRM, Gestão financeira e Gestão de estoque;
- Gestão completa de ordens de serviço;
- Workflow empresarial complexo;
- Aplicativo nativo Android/iOS;
- Integração obrigatória com ERP externo.

Esses recursos poderão ser incorporados posteriormente.

---

# 5. MODELO DE ACESSO

## 5.1 Área administrativa

A empresa terá uma área administrativa acessível pela rota `/painel`.

Inicialmente deverá permitir: configuração da empresa, logomarca, consulta dos relatórios, gerenciamento das taxonomias (listas suspensas dinâmicas), configuração de armazenamento e configurações gerais.

## 5.2 Área operacional

A criação dos relatórios **não exigirá cadastro individual dos técnicos**. 
A solução escolhida (link exclusivo, QR Code ou token) deverá manter a operação simples sem criar contas individuais para cada técnico.

---

# 6. CONFIGURAÇÃO DA EMPRESA

A empresa será configurada através do painel administrativo, permitindo configurar Nome, Logo (armazenada no disco local do servidor e com proporção mantida), informações adicionais, rodapé e formato do relatório.

---

# 7. UNIDADES

O sistema deverá trabalhar com unidades (Ex: Unidade Centro, Unidade Industrial).

## 7.1 Cadastro progressivo

O cadastro de unidades será **progressivo**. Quando o operador informar uma unidade que ainda não existe, o sistema deverá permitir cadastrá-la no próprio fluxo. A unidade ficará armazenada na base (tabela própria, sem termos chumbados no código) e aparecerá em autocompletes futuros.

O sistema deverá evitar duplicidades causadas por maiúsculas/minúsculas, espaços e acentuação.

---

# 8. SETORES

Cada OS poderá possuir **um ou vários setores** associados à unidade correspondente. O comportamento de cadastro progressivo e armazenamento em tabela própria será idêntico ao das unidades, permitindo que a aplicação ofereça dinamicamente "Unidade → Setores disponíveis".

---

# 9. ORDEM DE SERVIÇO

O número da OS será informado manualmente, através de um campo de texto obrigatório, sem assumir um formato específico (Ex: 123456, OS-123456, MAN-4587). Não haverá integração automática inicial com sistema externo.

---

# 10. HISTÓRICO DO SERVIÇO

O relatório possuirá um campo de texto livre de múltiplas linhas para registrar a descrição operacional do serviço antes da finalização.

---

# 11. TÉCNICOS ENVOLVIDOS

O relatório deverá possuir um campo de texto livre para informar um ou vários técnicos envolvidos. Não haverá necessidade de criar contas de usuário.

---

# 12. CAPTURA FOTOGRÁFICA

O usuário deverá poder abrir a câmera, fotografar, revisar, aceitar, refazer, adicionar observação e continuar fotografando. Não deverá existir limite artificial baixo, dependendo apenas do limite físico (disco local), desempenho e configuração da empresa.

---

# 13. GEOLOCALIZAÇÃO E ENDEREÇO

A plataforma solicitará acesso à localização para capturar latitude, longitude, precisão e data/hora.
As coordenadas serão convertidas em endereço via **OpenStreetMap (Nominatim)**. O sistema **não deverá inventar endereço**. Em caso de falha, manterá as coordenadas e registrará a indisponibilidade.

---

# 14. DATA E HORA

A data e hora oficial do relatório/fotografia deverão ser baseadas no **servidor**, registrando o timestamp recebido do servidor e o timezone configurado, evitando manipulação pelo relógio do celular.

---

# 15. MARCA D'ÁGUA DAS FOTOGRAFIAS

Cada fotografia deverá receber uma marca d'água injetada contendo (conforme disponibilidade): endereço, data, hora, coordenadas, número da OS e identificação da empresa. A apresentação será discreta e legível.

---

# 16. FOTOGRAFIA ORIGINAL E PROCESSADA

A aplicação manterá separadamente a fotografia original e a versão processada com marca d'água armazenadas no **disco local**. A versão processada irá para o relatório PDF e compartilhamentos.

---

# 17. OBSERVAÇÃO E ORDEM DAS FOTOGRAFIAS

Cada foto poderá ter sua própria descrição contextual. O usuário poderá alterar a ordem das imagens e essa ordenação será preservada na emissão dos documentos.

---

# 18. STATUS DO RELATÓRIO E FINALIZAÇÃO

Status (tratados em tabela dinâmica): Rascunho, Em execução, Finalizado.
Antes de finalizar, haverá uma tela de conferência. Após a confirmação, o servidor consolida tudo e impede alterações silenciosas. Alterações posteriores exigem novo registro (versionamento).

---

# 19. GERAÇÃO DE DOCUMENTOS E PDF

O sistema irá gerar um PDF profissional no backend através do **DomPDF**. O layout usará marcações compatíveis (tabelas e floats) garantindo que as fotos não sejam cortadas nas quebras de página. O PDF conterá capa padrão, logotipo e todas as fotos com marca d'água e observações. 
Também será possível gerar versão otimizada para impressão (A4) e versão em imagem.

---

# 20. COMPARTILHAMENTO VIA WHATSAPP

Fluxo: Finalizar relatório → Gerar PDF → Compartilhar (Web Share API, nativo ou fallback manual) → WhatsApp.

---

# 21. ARMAZENAMENTO E CONSULTA DE RELATÓRIOS

Os PDFs e Imagens ficarão no **disco local** da hospedagem, seguindo uma política de retenção configurável. O painel administrativo permitirá buscar relatórios através de filtros dinâmicos e visualizar toda a linha do tempo do serviço.

---

# 22. PWA, OFFLINE E PERMISSÕES

A aplicação operará como Progressive Web App (instalável, tela cheia, offline parcial). O usuário pode preencher dados e tirar fotos sem internet. A sincronização envia os dados pendentes quando houver conexão, respeitando a separação entre a hora local da captura e a hora oficial do servidor. 
Será solicitada permissão clara para câmera e localização.

---

# 23. SEGURANÇA E INTEGRIDADE

A plataforma terá HTTPS, proteção CSRF/XSS, validação rígida e não irá expor o volume interno de tabelas (uso obrigatório de **UUIDs** ao invés de IDs sequenciais). Hashes das fotos e logs de sistema (Auditoria) preservarão a validade da evidência.

---

# 24. BANCO DE DADOS (Estrutura Blindada)

O banco relacional utilizará identificadores `UUID` em todas as tabelas primárias para ocultar sequenciamentos operacionais no frontend.

## companies

```text
id (UUID)
name
logo_path
settings
created_at
updated_at
