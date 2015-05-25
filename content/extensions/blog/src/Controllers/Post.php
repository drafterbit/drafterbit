<?php namespace Drafterbit\Blog\Controllers;

use Drafterbit\Base\Controller\Backend as BackendController;
use Drafterbit\Component\Validation\Exceptions\ValidationFailsException;

class Post extends BackendController
{
    public function index()
    {
        $status = 'all';
        $data['id']        = 'posts';
        $data['title']     = __('Posts');
        $data['status']    = $status;
        $data['action']    = admin_url('posts/trash');

        return $this->render('@blog/admin/index', $data);
    }
 
    public function trash()
    {
        $post = $this['input']->post();
        $model = $this->model('Post');

        $postIds = $post['posts'];

        switch($post['action']) {
            case "trash":
                $model->trash($postIds);
                break;
            case 'delete':
                $model->delete($postIds);
            case 'restore':
                $model->restore($postIds);
                break;
            default:
                break;
        }
    }

    public function data($status)
    {
        $posts = $this->model('@blog\Post')->all(['status' => $status]);
        
        $editUrl = admin_url('blog/edit');

        $pagesArr  = [];

        foreach ($posts as $post) {
            $data = [];
            $data['id'] = $post['id'];
            $data['title'] = $post['title'];
            $data['author'] = $post['authorName'];
            $data['user_id'] = $post['user_id'];

            $data['updated_at'] = $this['time']->parse($post['updated_at'])->format('d F Y H:i');

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
        $tagOptionsArray = $this->model('Tag')->all();
        $tagOptions = '[';
        foreach ($tagOptionsArray as $tO) {
            $tO = (object) $tO;
            $tagOptions .= "'{$tO->label}',";
        }
        $tagOptions = rtrim($tagOptions, ',').']';

        $categories = $this->model('@blog\Category')->tree();

        if ('new' == $id) {
            $data = [
                'postId' => $id,
                'postTitle' => null,
                'slug' => null,
                'content' => null,
                'postCategories' => [],
                'tagOptions' => $tagOptions,
                'tags' => [],
                'revisions' => [],
                'status' => 1,
                'publishDate' => null,
                'title' => __('New Post'),
            ];
        } else {
            $model = $this->model('Post'); 
            $post = $model->getBy('id', $id);
            $post->tags = $model->getTags($id);
            $post->revisions = array_map(function($item) {
                $item['time_human'] = $this['time']->parse($item['time'])->diffForHumans();
                $item['time'] = $this['time']->parse($item['time'])->format('d F Y, @H:i');
                return $item;
            }, $model->getRevisions($id));

            $tags = [];
            foreach ($post->tags as $tag) {
                $tag = (object) $tag;
                $tags [] = $tag->label;
            }

            $post->categories = $model->getCategories($id);
            $postCategories = [];

            foreach ($post->categories as $c) {
                $postCategories[] = $c['id'];
            }

            $data = [
                'postId' => $id,
                'postTitle' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
                'revisions' => $post->revisions,
                'postCategories' => $postCategories,
                'tags' => $tags,
                'tagOptions' => $tagOptions,
                'status' => $post->status,
                'publishDate' => $post->published_at,
                'title' => __('Edit Post'),
            ];
        }

        $data['categories'] = $categories;
        $data['id'] = 'post-edit';
        $data['action'] = admin_url('posts/save');
        
        return $this->render('@blog/admin/edit', $data);
    }

    public function save()
    {
        $model = $this->model('Post');
        
        try {

            $validator = $this['validation.form'];
            $rules = $this['config']->get('validation.post@blog');
            $validator->setRules($rules);

            $postData = $this['input']->post();

            if(empty($postData['title'])) {
                $validator->setRule('slug', 'optional');
            }

            if(empty($postData['slug'])) {
                $postData['slug'] = slug($postData['title']);
            }

            $validator->validate($postData);
            
            $id = $postData['id'];

            if (is_numeric($id)) {

                $data = $this->createUpdateData($postData);

                $this->createRevision($id, $data['title'], $data['content']);

                $model->update($data, $id);
            
            } else {
                $data = $this->createInsertData($postData);
                $id = $model->insert($data);
            }

            // delete all related tag first
            $model->clearTag($id);
            if (isset($postData['tags'])) {
                $this->insertTags($postData['tags'], $id);
            }
            //at this point, we'll remove unused tags
            $this->model('Tag')->cleanUnused();

            if (isset($postData['categories'])) {
                $this->insertCategories($postData['categories'], $id);
            }

            // @todo log here
            return $this->jsonResponse(['message' => __('Post succesfully saved'), 'status' => 'success', 'id' => $id]);

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

    /**
     * Create a post revision
     *
     * @param int $id
     */
    private function createRevision($id, $new_title, $new_content)
    {
        $current = $this->model('Post')->getOneBy('id', $id);

        if($current['title'] == $new_title &&
            $current['content'] == $new_content) {
            return;
        }

        $insert_data = [
            'title' => $current['title'],
            'content' => $current['content'],
            'created_at' => $this['time']->now(),
            'type' => 'revision:'.$id,
            'user_id' => $this['session']->get('user.id')
        ];

        $this->model('Post')->insert($insert_data);
    }

    /**
     * Parse post data to insert to db
     *
     * @param  array $post
     * @return array
     */
    protected function createInsertData($post, $isUpdate = false)
    {
        $data = [];
        
        $data['slug']       = $post['slug'];
        $data['title']      = $post['title'];
        $data['status']     = $post['status'];
        $data['content']    = $post['content'];
        $data['type']       = 'standard';
        $data['user_id']    = $this['session']->get('user.id');
        $data['updated_at'] = $this['time']->now();
        
        if (! $isUpdate) {
            $data['created_at'] = $this['time']->now();
        }

        if($data['status'] == 1) {
            $data['published_at'] = $post['publish-date'];
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

    protected function insertTags($tags, $postId)
    {
        $post = $this->model('Post');
        $tag = $this->model('Tag');

        foreach ($tags as $t) {
            if (! $tagId = $tag->getIdBy('label', $t)) {
                $tagId = $tag->save($t);
            }

            $post->addTag($tagId, $postId);
        }
    }

    protected function insertCategories($categories, $postId)
    {
        $post = $this->model('Post');

        //delete all related tag first
        $post->clearCategories($postId);

        foreach ($categories as $c) {
            $post->addCategory($c, $postId);
        }
    }

    public function setting()
    {
        $data['title'] = __('Blog Setting');

        $model = $this->model('@system\System');

        if ($post = $this['input']->post()) {

            $newSetting = [
                'feed.shows'         => $post['feed_shows'],
                'post.per_page'      => $post['post_perpage'],
                'feed.content'       => $post['feed_content'],
                'comment.moderation' => $post['comment_moderation']
            ];

            $model->updateSetting($newSetting);

            $this['template']->addGlobal('messages', [['text' => "Setting updated", "type" => 'success']]);
        }

        $data['mode']        = $model->get('comment.moderation');
        $data['postPerpage'] = $model->get('post.per_page', 5);
        $data['feedShows']   = $model->get('feed.shows', 10);
        $data['feedContent'] = $model->get('feed.content', 2);
        return $this->render('@blog/admin/setting', $data);
    }
}