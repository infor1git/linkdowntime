# Link Downtime Manager - Plugin para GLPI 10.0.16

## Descrição

Plugin completo para gerenciamento de quedas de link de rede no GLPI, desenvolvido especificamente para empresas que precisam monitorar e registrar incidentes de conectividade com seus provedores de internet.

## Funcionalidades

### 📋 Registro de Quedas
- **Data e horário de início da queda**
- **Data e horário de fim da queda**
- **Data e horário da comunicação com a operadora**
- **Campo de observações** para detalhes adicionais
- **Seleção de localização** (integrado com localizações do GLPI)
- **Seleção de fornecedor** (filtrado por fornecedores com tag ID = 1)

### 📊 Dashboard Avançado
- **Estatísticas globais da empresa**
- **Estatísticas por localização**
- **Filtro por ano** para análise histórica

## Instalação

1. **Download**: Baixe o arquivo ZIP
2. **Extração**: Extraia na pasta `plugins` do GLPI
3. **Ativação**: Acesse `Configurar > Plugins` no GLPI
4. **Instalação**: Clique em "Instalar" e depois "Ativar"

## Pré-requisitos

- GLPI 10.0.16 ou superior
- Plugin Tags (para filtragem de fornecedores)
- Permissões de administrador para instalação

## Licença

GPL v3+ - Software livre para uso e modificação
