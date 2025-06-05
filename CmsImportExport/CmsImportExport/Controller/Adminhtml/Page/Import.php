<?php
namespace Twodev\CmsImportExport\Controller\Adminhtml\Page;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\File\Csv;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class Import extends Action
{
    protected $csvProcessor;
    protected $pageFactory;
    protected $filesystem;
    protected $directoryList;

    public function __construct(
        Context $context,
        Csv $csvProcessor,
        PageFactory $pageFactory,
        Filesystem $filesystem,
        DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->csvProcessor = $csvProcessor;
        $this->pageFactory = $pageFactory;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
    }

    public function execute()
    {
        $file = $this->getRequest()->getFiles('import_file');
        if ($file && isset($file['tmp_name'])) {
            try {
                $csvData = $this->csvProcessor->getData($file['tmp_name']);
                $header = array_shift($csvData);
                foreach ($csvData as $row) {
                    $data = array_combine($header, $row);

                    if (isset($data['stores']) && !is_array($data['stores'])) {
                        $data['stores'] = explode(',', $data['stores']);
                    }

                    $originalIdentifier = $data['identifier'];
                    $identifier = $originalIdentifier;
                    $i = 1;

                    while ($this->pageFactory->create()->load($identifier, 'identifier')->getId()) {
                        $identifier = $originalIdentifier . '-new' . ($i > 1 ? "-$i" : '');
                        $i++;
                    }

                    $data['identifier'] = $identifier;

                    $page = $this->pageFactory->create();
                    $page->setData($data)->save();
                }
                $this->messageManager->addSuccessMessage(__('CMS Pages imported successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $this->_redirect('cms/page/index');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Cms::page');
    }
}
