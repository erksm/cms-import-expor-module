<?php
namespace Twodev\CmsImportExport\Controller\Adminhtml\Page;

use Magento\Backend\App\Action;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Export extends Action implements HttpPostActionInterface
{
    protected $pageFactory;
    protected $fileFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $pageFactory,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        $pageId = $this->getRequest()->getParam('page_id');

        if (!$pageId) {
            $this->messageManager->addErrorMessage(__('No page ID provided.'));
            return $this->_redirect('cms/page/index');
        }

        $page = $this->pageFactory->create()->load($pageId);

        if (!$page->getId()) {
            $this->messageManager->addErrorMessage(__('Page not found.'));
            return $this->_redirect('cms/page/index');
        }

        $headers = [
            'title', 'content_heading', 'identifier', 'content', 'is_active', 'page_layout',
            'meta_title', 'meta_keywords', 'meta_description',
            'sort_order', 'layout_update_xml', 'stores'
        ];

        $csvContent = '"' . implode('","', $headers) . '"' . "\n";

        $row = [
            $page->getTitle(),
            $page->getContentHeading(),
            $page->getIdentifier(),
            str_replace('"', '""', $page->getContent() ?? ''),
            $page->getIsActive(),
            $page->getPageLayout() ?? '',
            $page->getMetaTitle() ?? '',
            $page->getMetaKeywords() ?? '',
            $page->getMetaDescription() ?? '',
            $page->getSortOrder() ?? '',
            str_replace('"', '""', $page->getLayoutUpdateXml() ?? ''),
            implode(',', $page->getStores())
        ];

        $csvContent .= '"' . implode('","', $row) . '"' . "\n";

        return $this->fileFactory->create(
            'cms_page_' . $page->getIdentifier() . '.csv',
            $csvContent,
            DirectoryList::VAR_DIR,
            'text/csv'
        );
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Cms::page');
    }
}
