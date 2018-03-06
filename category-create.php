<?php
/**
* Baseado em conteúdo disponível na internet
* Este script criará várias categorias dentro do Magento de uma só vez
* Basta inserir as categorias desejadas e copiar o arquivo para raiz da sua loja
* 
* @author Ronaldo Diniz <ronaldo@ronaldodiniz.com.br>
* @license GNU
* 
*/

// pra que este script funcione corretamente informe o ID do do seu Diretorio Raiz
$diretorio_raiz = 2;
// array cria categoria/subcategoria, não precisa criar categoria principal anteriromente
$diretorios = array(
						'Categoria1/Sub Categoria1',
						'Categoria1/Sub Categoria2',
						'Categoria1/Sub Categoria3',
						'Categoria1/Sub Categoria4',
						'Roupas/Masculino/Calças',
						'Roupas/Feminino/Calças',
						'Roupas/Feminino/Macaquinhos',
						'Roupas/Feminino/Vestidos',
						'Roupas/Feminino/Shorts',
						'Prompções/Para Ele',
						'Prompções/Para Ela'
					);
//Define local de instalação
define('MAGENTO', realpath(dirname(__FILE__)));
//inicia Aplicação do Magento
require_once MAGENTO . '/app/Mage.php';
umask(0);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

// esta página irá imprimir as ações no seu navegador
echo '<pre>';

foreach ($diretorios as $value) {
	$pastas = explode('/', $value);
	for ($i=0; $i < count($pastas); $i++) { 
		
		if ($i == 0) {
			$idpai = $diretorio_raiz;
		} else {
			$categorypai = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $pastas[$i - 1]); 
			$cat= $categorypai->getData();
			$idpai = ($cat[0][entity_id])? $cat[0][entity_id] : false ;
		}
		
		$category = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $pastas[$i]); 
		$cat= $category->getData();
		$categoryid = ($cat[0][entity_id])? $cat[0][entity_id] : false ;

		if(!$categoryid) {
			$data['general']['path'] = $idpai;
			$data['general']['name'] = $pastas[$i];
			$data['general']['meta_title'] = "";
			$data['general']['meta_description'] = "";
			$data['general']['is_active'] = "1";
			$data['general']['url_key'] = "";
			$data['general']['display_mode'] = "PRODUCTS";
			$data['general']['is_anchor'] = 0;
			$data['category']['parent'] = $idpai; // 3 top level
			$storeId = 0;
			createCategory($data, $storeId);
			sleep(0.5);
			unset($data);
		}
	}
}

echo '</pre>';


function createCategory($data, $storeId) {
    echo "Starting {$data['general']['name']} [{$data['category']['parent']}] ...";
    $category = Mage::getModel('catalog/category');
    $category->setStoreId($storeId);

    // Fix must be applied to run script
    // http://www.magentocommerce.com/boards/appserv/main.php/viewreply/157328/

    if (is_array($data)) {
        $category->addData($data['general']);
        if (!$category->getId()) {
            $parentId = $data['category']['parent'];
            if (!$parentId) {
                if ($storeId) {
                    $parentId = Mage::app()->getStore($storeId)->getRootCategoryId();
                } else {
                    $parentId = Mage_Catalog_Model_Category::TREE_ROOT_ID;
                }
            }

            $parentCategory = Mage::getModel('catalog/category')->load($parentId);
            $category->setPath($parentCategory->getPath());
        }

        /**
         * Check "Use Default Value" checkboxes values
         */
        if ($useDefaults = $data['use_default']) {
            foreach($useDefaults as $attributeCode) {
                $category->setData($attributeCode, null);
            }
        }

        $category->setAttributeSetId($category->getDefaultAttributeSetId());
	    if (isset($data['category_products']) && !$category->getProductsReadonly()) {
            $products = array();
            parse_str($data['category_products'], $products);
            $category->setPostedProducts($products);
            }

        try {
            $category->save();
            echo "Suceeded <br /> ";
        } catch(Exception $e) {
            echo "Failed <br />";
        }
    }
}
