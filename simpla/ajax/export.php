<?php

chdir('../..');
require_once('api/Simpla.php');

class ExportAjax extends Simpla
{	
	private $columns_names = array(
			'category'=>         '���������',
			'name'=>             '�����',
			'price'=>            '����',
			'url'=>              '�����',
			'visible'=>          '�����',
			'featured'=>         '�������������',
			'brand'=>            '�����',
			'variant'=>          '�������',
			'compare_price'=>    '������ ����',
			'sku'=>              '�������',
			'stock'=>            '�����',
			'meta_title'=>       '��������� ��������',
			'meta_keywords'=>    '�������� �����',
			'meta_description'=> '�������� ��������',
			'annotation'=>       '���������',
			'body'=>             '��������',
			'images'=>           '�����������'
			);
			
	private $column_delimiter = ';';
	private $subcategory_delimiter = '/';
	private $products_count = 5;
	private $export_files_dir = 'simpla/files/export/';
	private $filename = 'export.csv';

	public function fetch()
	{
		// ������ ������ ������ 1251
		setlocale(LC_ALL, 'ru_RU.1251');
		$this->db->query('SET NAMES cp1251');
	
		// ��������, ������� ������������
		$page = $this->request->get('page');
		if(empty($page) || $page==1)
		{
			$page = 1;
			// ���� ������ ������� - ������ ������ ���� ��������
			if(is_writable($this->export_files_dir.$this->filename))
				unlink($this->export_files_dir.$this->filename);
		}
		
		// ��������� ���� �������� �� ����������
		$f = fopen($this->export_files_dir.$this->filename, 'ab');
		
		// ������� � ������ ������� �������� �������
		$features = $this->features->get_features();
		foreach($features as $feature)
			$this->columns_names[$feature->name] = $feature->name;
		
		// ���� ������ ������� - ������� � ������ ������ �������� �������
		if($page == 1)
		{
			fputcsv($f, $this->columns_names, $this->column_delimiter);
		}
		
		// ��� ������
		$products = array();
 		foreach($this->products->get_products(array('page'=>$page, 'limit'=>$this->products_count)) as $p)
 		{
 			$products[$p->id] = (array)$p;
 			
	 		// �������� �������
	 		$options = $this->features->get_product_options($p->id);
	 		foreach($options as $option)
	 		{
	 			if(!isset($products[$option->product_id][$option->name]))
					$products[$option->product_id][$option->name] = $option->value;
	 		}

 			
 		}
 		
 		if(empty($products))
 			return false;
 		
 		// ��������� �������
 		$categories = $this->categories->get_product_categories(array_keys($products));
 		foreach($categories as $category)
 		{
 			// ���� ��������� � ������ ��� ���� - �� ��������� (�� ����, �������������� ������ ������)
 			if(isset($products[$category->product_id]) && empty($products[$category->product_id]['category']))
 			{
 				$path = array();
 				$cat = $this->categories->get_category((int)$category->category_id);
 				if(!empty($cat))
 				{
	 				// ��������� ������������ ���������
	 				foreach($cat->path as $p)
	 					$path[] = str_replace($this->subcategory_delimiter, '\\'.$this->subcategory_delimiter, $p->name);
	 				// ��������� ��������� � ������ 
	 				$products[$category->product_id]['category'] = implode('/', $path);
 				}
 			}
 		}
 		
 		// ����������� �������
 		$images = $this->products->get_images(array('product_id'=>array_keys($products)));
 		foreach($images as $image)
 		{
 			// ��������� ����������� � ������ ����� �������
 			if(empty($products[$image->product_id]['images']))
 				$products[$image->product_id]['images'] = $image->filename;
 			else
 				$products[$image->product_id]['images'] .= ', '.$image->filename;
 		}
 
 		$variants = $this->variants->get_variants(array('product_id'=>array_keys($products)));

		foreach($variants as $variant)
 		{
 			$result = null;
 			if(isset($products[$variant->product_id]))
 			{
	 			$p                    = $products[$variant->product_id];
	 			$p['variant']         = $variant->name;
	 			$p['price']           = $variant->price;
	 			$p['compare_price']   = $variant->compare_price;
	 			$p['sku']             = $variant->sku;
	 			$p['stock']           = $variant->stock;
	 			if($variant->infinity)
	 				$p['stock']           = '';
	 			
	 			foreach($this->columns_names as $internal_name=>$column_name)
	 			{
	 				if(isset($p[$internal_name]))
		 				$result[$internal_name] = $p[$internal_name];
		 			else
		 				$result[$internal_name] = '';
	 			}
	 			fputcsv($f, $result, $this->column_delimiter);
	 		}
		}
		
		$total_products = $this->products->count_products();
		
		if($this->products_count*$page < $total_products)
			return array('end'=>false, 'page'=>$page, 'totalpages'=>$total_products/$this->products_count);
		else
			return array('end'=>true, 'page'=>$page, 'totalpages'=>$total_products/$this->products_count);		

		fclose($f);

	}
	
}

$export_ajax = new ExportAjax();
$json = json_encode($export_ajax->fetch());
header("Content-type: application/json; charset=utf-8");
header("Cache-Control: must-revalidate");
header("Pragma: no-cache");
header("Expires: -1");		
print $json;