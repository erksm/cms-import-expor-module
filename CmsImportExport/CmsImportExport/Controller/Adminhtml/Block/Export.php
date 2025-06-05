<?php
namespace Twodev\CmsImportExport\Controller\Adminhtml\Block;

use Magento\Backend\App\Action;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Export extends Action implements HttpPostActionInterface
{
    protected $blockFactory;
    protected $fileFactory;

    public function __construct(
        Action\Context $context,
        BlockFactory $blockFactory,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->blockFactory = $blockFactory;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        $blockId = $this->getRequest()->getParam('block_id');

        if (!$blockId) {
            $this->messageManager->addErrorMessage(__('No block ID provided.'));
            return $this->_redirect('cms/block/index');
        }

        $block = $this->blockFactory->create()->load($blockId);

        if (!$block->getId()) {
            $this->messageManager->addErrorMessage(__('Block not found.'));
            return $this->_redirect('cms/block/index');
        }

        $csvContent = '"' . __('Title') . '","' . __('Identifier') . '","' . __('Store') . '","' . __('Content') . '"' . "\n";
        $csvContent .= '"' . $block->getTitle() . '","' . $block->getIdentifier() . '","' . implode(',', $block->getStores()) . '","' . str_replace('"', '""', $block->getContent()) . '"' . "\n";

        return $this->fileFactory->create(
            'cms_block_' . $block->getIdentifier() . '.csv',
            $csvContent,
            DirectoryList::VAR_DIR,
            'text/csv'
        );
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Cms::block');
    }
}
