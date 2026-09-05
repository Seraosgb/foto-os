### Aditivo 01 ao Documento Mestre — FotoOS

**Identificação:** Aditivo Técnico nº 01/2026

**Referência:** Especificação Funcional — FotoOS (Versão 1.1)

**Data de Vigência:** 05/09/2026

**Status:** Aprovado e Homologado

**1. Objeto**

O presente aditivo tem por finalidade postergar temporariamente a exigência de entrega da funcionalidade descrita no **Item 1.15 (Geração de versão em imagem)** da Seção 1 do Documento Mestre para versões subsequentes à entrega do MVP.

**2. Justificativa Técnica**

* **Otimização de Armazenamento e Infraestrutura:** A conversão de PDFs em imagens rasterizadas (`.jpg`/`.png`) exige bibliotecas de sistema pesadas (como `Imagick` com binários do `Ghostscript`), o que consome memória e armazenamento excessivo no disco local compartilhado do servidor.

* **Atendimento Pleno ao Usuário Final:** O documento gerado em formato PDF A4 vetorial já atende integralmente à visualização mobile, impressão física nítida e compartilhamento imediato via WhatsApp e Web Share API.

**3. Alterações nos Itens do Documento Mestre**

* **Item 1.15 (Visão Geral):** Fica reclassificado da lista de entregáveis imediatos do MVP para o rol da **Seção 4 (Fora do Escopo Inicial)**.

* **Seção 19 (Geração de Documentos e PDF):** Mantém-se obrigatória a geração do PDF via DomPDF, suspendendo-se a renderização página a página em formato de imagem estática.
