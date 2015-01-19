<?php namespace Drafterbit\Extensions\Pages\Controllers;

use Drafterbit\Component\Validation\Exceptions\ValidationFailsException;
use Drafterbit\Extensions\System\BackendController;
use Carbon\Carbon;

class Pages extends BackendController
{

    public function index()
    {            
        $status = 'untrashed';

        $pages = $this->model('@pages\Pages')->all(['status' => $status]);
        
        $data['status'] = $status;
        $data['pages'] = $pages;
        $data['id'] = 'pages';
        $data['title'] =  __('Pages');
        $data['action'] = admin_url('pages/trash');
        $data['pagesTable'] = $this->dataTable('pages', $this->_tableHeader(), $pages);

        return $this->render('@pages/admin/index', $data);
    }

    public function trash()
    {
        $post = $this->get('input')->post();

        $pageIds = $post['pages'];

        switch($post['action']) {
        case "trash":
            $this->model('@pages\Pages')->trash($pageIds);
            break;
        case 'delete':
            $this->model('@pages\Pages')->delete($pageIds);
        case 'restore':
            $this->model('@pages\Pages')->restore($pageIds);
            break;
        default:
            break;
        }
    }

    public function filter($status)
    {
        $pages = $this->model('@pages\Pages')->all(['status' => $status]);
        
        $editUrl = admin_url('pages/edit');

        $pagesArr  = array();

        foreach ($pages as $page) {

            $page = (object)$page;

            $data = array();
            $data[] = '<input type="checkbox" name="pages[]" value="'.$page->id.'">';
            $data[] = $status !== 'trashed' ? "<a class='page-edit-link' href='$editUrl/{$page->id}'> {$page->title}</a>" : $page->title;
            $data[] = $page->created_at;

            if($status == 'trashed') {
                $s = ucfirst($status);
            } else {
                $s = $page->status == 1 ? 'Published' : 'Unpublished';
            }

            $data[] = $s;

            $pagesArr[] = $data;
        }

        $ob = new \StdClass;
        $ob->data = $pagesArr;
        $ob->recordsTotal= count($pagesArr);
        $ob->recordsFiltered = count($pagesArr);

        return $this->jsonResponse($ob);
    }

    public function edit($id)
    {
        if($id == 'new') {

            $data = array(
                'title'     => __('Create New Page'),

                'pageId'     => null,
                'pageTitle' => null,
                'slug'         => null,
                'content'     => null,
                'layout'     => null,
                'status'     => 1,
            );

        } else {

            $page = (object) $this->model('@pages\Pages')->getSingleBy('id', $id);
            $data = array(
                'title'     => __('Edit Page'),

                'pageId'     => $id,
                'pageTitle' => $page->title,
                'slug'         => $page->slug,
                'content'     => $page->content,
                'layout'    => $page->layout,
                'status'     => $page->status,                
            );
        }

        $data['id'] = 'page-edit';
        $data['action'] = admin_url('pages/save');
        $data['layoutOptions'] = $this->getLayoutOptions();

        return $this->render('@pages/admin/editor', $data);
    }

    /**
     * Save submitted post data
     */
    public function save()
    {
        try {

            $postData = $this->get('input')->post();

            $this->validate('page', $postData);

            $id = $postData['id'];

            if($id) {
                $data = $this->createUpdateData($postData);
                $this->model('@pages\Pages')->update($data, $id);            
                
            } else {

                $data = $this->createInsertData($postData);
                $id = $this->model('@pages\Pages')->insert($data);
            }

            $this->get('cache')->delete('pages');

            return $this->jsonResponse(['message' => __('Page succesfully saved'), 'status' => 'success', 'id' => $id]);

        } catch (ValidationFailsException $e) {
            
            return $this->jsonResponse(
                ['error' => [
                    'type' => 'validation',
                    'message' => $e->getMessage(),
                    'messages' => $e->getMessages()
                ]
                ]
            );
        }
    }

    private function _tableHeader()
    {
        $editUrl = admin_url('pages/edit');
        $formatTitle = function($value, $item) use ($editUrl) {
            return "<a href='$editUrl/{$item['id']}'>$value</i></a>"; 
        };
        $formatStatus = function($value, $item) {
            return $value == 1 ? 'Published' : 'Unpublished'; 
        };

        return [
            ['field' => 'title', 'label' => 'Title', 'width' => '70%', 'format' => $formatTitle ],
            ['field' => 'created_at', 'label' => 'Created', 'width' => '20%'],
            ['field' => 'status', 'label' => 'Status', 'width' => '10%', 'format' => $formatStatus ]
        ];
    }

    /**
     * get available layout from current themes
     *
     * @return array
     */
    private function getLayoutOptions()
    {
        $layouts = $this->get('finder')->in($this->get('path.theme').'layout')->files();
        $options = array();
        foreach ($layouts as $layout) {
            $options[$layout->getFileName()] = $layout->getFileName();
        }

        return $options;
    }


    /**
     * Parse post data to insert to db
     *
     * @param  array $post
     * @return array
     */
    protected function createInsertData($post, $isUpdate = false)
    {
        $data = array();
        $data['title'] = $post['title'];
        $data['slug'] = $post['slug'];
        $data['content'] = $post['content'];
        $data['layout'] = $post['layout'];
        $data['status'] = $post['status'];
        $data['user_id'] = $this->get('session')->get('user.id');
        $data['updated_at'] = Carbon::now();
        
        if (! $isUpdate) {
            $data['created_at'] = Carbon::now();
        }

        return $data;
    }

    /**
     * Parse post data for update
     *
     * @param  array $post
     * @return array
     */
    public function createUpdateData($post)
    {
        return $this->createInsertData($post, true);
    }
}