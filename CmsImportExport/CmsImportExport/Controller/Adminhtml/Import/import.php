<?php
namespace Twodev\CmsImportExport\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\File\Csv as CsvReader;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class Import extends Action
{
    const ADMIN_RESOURCE = 'Twodev_CmsImportExport::cms_export';

    protected $csvReader;
    protected $blockFactory;
    protected $filesystem;

    public function __construct(
        Context $context,
        CsvReader $csvReader,
        BlockFactory $blockFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->csvReader = $csvReader;
        $this->blockFactory = $blockFactory;
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        $data = $this->getRequest()->getFiles('import_file');
        if (!isset($data['tmp_name'])) {
            $this->messageManager->addErrorMessage(__('No file uploaded.'));
            return $this->_redirect('cms/block/index');
        }

        $csvData = $this->csvReader->getData($data['tmp_name']);
        $headers = array_shift($csvData);

        foreach ($csvData as $row) {
            if (count($headers) != count($row)) {
                $this->messageManager->addErrorMessage(__('Row data does not match header columns.'));
                continue;
            }

            $blockData = array_combine($headers, $row);

            if (!isset($blockData['Identifier'])) {
                $this->messageManager->addErrorMessage(__('Missing Identifier in CSV data.'));
                continue;
            }

            $identifier = $blockData['Identifier'];
            $originalIdentifier = $identifier;
            $counter = 1;

            while ($this->blockFactory->create()->load($identifier, 'identifier')->getId()) {
                $identifier = $originalIdentifier . '-new' . ($counter > 1 ? $counter : '');
                $counter++;
            }

            $blockData['Identifier'] = $identifier;

            $block = $this->blockFactory->create();
            $block->setTitle($blockData['Title'] ?? ''); 
            $block->setIdentifier($blockData['Identifier']);
            $block->setContent($blockData['Content'] ?? '');
            $block->setIsActive(1);
            $block->setStores(array_map('intval', explode(',', $blockData['Store'])));
            $block->save();
        }

        $this->messageManager->addSuccessMessage(__('CMS Blocks imported successfully.'));
        return $this->_redirect('cms/block/index');
    }
}
