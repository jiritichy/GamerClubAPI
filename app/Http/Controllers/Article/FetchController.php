<?php

namespace App\Http\Controllers\Article;

use QL\QueryList;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use App\Http\Model\Article\{
    News,
    Ref,
    Tag
};
use Illuminate\Support\Facades\Log;

class FetchController extends Controller
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $towP = $this->towP();
        $yys = $this->yys();
        $indienova = $this->indienova();
        $vgtime = $this->vgtime();


        return [
            '2p' => $towP,
            '游研社' => $yys,
            'indienova' => $indienova,
            'vgtime' => $vgtime
        ];
    }

    protected function towP()
    {
        $data = QueryList::get('https://www.2p.com/articles', [
            'pageSize' => '21',
            'pageNo' => '1'
        ])
            ->rules([
                'title' => array('.game-list li .tit', 'text'),
                'description' => array('.game-list li .summary', 'text'),
                'image' => array('.game-list li .pic img', 'data-src'),
                'author' => array('.game-list li .user', 'text'),
                'author_avatar' => array('.game-list li .user img', 'src'),
                'game_name' => array('.game-list li .game', 'text'),
                'tag' => array('.game-list li .pic .tag', 'text'),
                'ref_link' => array('.game-list li a', 'href')
            ])
            ->queryData();

        $collection = collect($data)->each(function ($item, $key) {
            $title = $item['title'];
            $tag = $item['tag'];
            $ref_name = '2p';
            $ref_top_domain = '//www.2p.com';

            $tag_id = Tag::firstOrCreate(
                ['name' => $tag],
                ['name' => $tag]
            )->id;

            $ref_id = Ref::firstOrCreate(
                ['name' => $ref_name],
                [
                    'name' => $ref_name,
                    'top_domain' => $ref_top_domain
                ]
            )->id;

            News::firstOrCreate(
                ['title' => $title],
                [
                    'title' => $title,
                    'description' => $item['description'] ?? null,
                    'image' => $item['image'] ?? null,
                    'author' => $item['author'] ?? null,
                    'author_avatar' => $item['author_avatar'] ?? null,
                    'game_name' => $item['game_name'] ?? null,
                    'tag_id' => $tag_id,
                    'ref_id' => $ref_id,
                    'ref_link' => $item['ref_link']
                ]
            );
            Log::info('拉取文章: ' . $ref_name . ' - ' . $title);
        });

        return '2p fecth success';
    }

    protected function yys()
    {
        $res = $this->client->request('GET', 'https://www.yystv.cn/boards/get_board_list_by_page?page=0&value=news');

        $collection = collect(json_decode($res->getBody())->data)->each(function ($item, $key) {
            $title = $item->title;
            $tag = '趣闻';
            $ref_name = '游研社';
            $ref_top_domain = '//www.yystv.cn';

            $tag_id = Tag::firstOrCreate(
                ['name' => $tag],
                ['name' => $tag]
            )->id;

            $ref_id = Ref::firstOrCreate(
                ['name' => $ref_name],
                [
                    'name' => $ref_name,
                    'top_domain' => $ref_top_domain
                ]
            )->id;

            News::firstOrCreate(
                ['title' => $title],
                [
                    'title' => $title,
                    'description' => $item->preface ?? null,
                    'image' => $item->cover ?? null,
                    'author' => $item->author ?? null,
                    'tag_id' => $tag_id,
                    'ref_id' => $ref_id,
                    'ref_link' => '//www.yystv.cn/p/' . $item->id
                ]
            );
            Log::info('拉取文章: ' . $ref_name . ' - ' . $title);
        });


        return 'yys fetch success';
    }

    protected function indienova()
    {
        $data = QueryList::get('https://indienova.com/channel/news')
            ->rules([
                'title' => array('.indienova-channel-border .article-panel h4 a', 'text'),
                'description' => array('.indienova-channel-border .article-panel p', 'text'),
                'image' => array('.indienova-channel-border .article-panel .article-image a img', 'src'),
                'ref_link' => array('.indienova-channel-border .article-panel h4 a', 'href')
            ])
            ->queryData();

        $collection = collect($data)->each(function ($item, $key) {
            $title = $item['title'];
            $tag = '资讯';
            $ref_name = 'indienova';
            $ref_top_domain = '//indienova.com';
            $image = str_replace("_t205", "", $item['image'] ?? null)  ?? null;

            $tag_id = Tag::firstOrCreate(
                ['name' => $tag],
                ['name' => $tag]
            )->id;

            $ref_id = Ref::firstOrCreate(
                ['name' => $ref_name],
                [
                    'name' => $ref_name,
                    'top_domain' => $ref_top_domain
                ]
            )->id;

            News::firstOrCreate(
                ['title' => $title],
                [
                    'title' => $title,
                    'description' => $item['description'] ?? null,
                    'image' => $image ?? null,
                    'author' => $item['author'] ?? null,
                    'author_avatar' => $item['author_avatar'] ?? null,
                    'game_name' => $item['game_name'] ?? null,
                    'tag_id' => $tag_id,
                    'ref_id' => $ref_id,
                    'ref_link' => $ref_top_domain . $item['ref_link']
                ]
            );
            Log::info('拉取文章: ' . $ref_name . ' - ' . $title);
        });

        return 'indienova fecth success';
    }

    protected function vgtime()
    {
        $data = QueryList::get('https://www.vgtime.com')
            ->rules([
                'title' => array('.game_news_box .vg_list .small_small li .info_box a h2', 'text'),
                'description' => array('.game_news_box .vg_list .small_small li .info_box p', 'text'),
                'image' => array('.game_news_box .vg_list .small_small li .img_box a img', 'data-url'),
                'author' => array('.game_news_box .vg_list .small_small li .info_box .fot_box .left span', 'text'),
                'ref_link' => array('.game_news_box .vg_list .small_small li .info_box a', 'href')
            ])
            ->queryData();

        $collection = collect($data)->each(function ($item, $key) {
            $title = $item['title'];
            $tag = '资讯';
            $ref_name = 'vgtime';
            $ref_top_domain = '//www.vgtime.com';
            $image = str_replace("?x-oss-process=image/resize,m_pad,color_000000,w_640,h_400", "", $item['image'] ?? null)  ?? null;

            $tag_id = Tag::firstOrCreate(
                ['name' => $tag],
                ['name' => $tag]
            )->id;

            $ref_id = Ref::firstOrCreate(
                ['name' => $ref_name],
                [
                    'name' => $ref_name,
                    'top_domain' => $ref_top_domain
                ]
            )->id;

            News::firstOrCreate(
                ['title' => $title],
                [
                    'title' => $title,
                    'description' => $item['description'] ?? null,
                    'image' => $image ?? null,
                    'author' => $item['author'] ?? null,
                    'author_avatar' => $item['author_avatar'] ?? null,
                    'game_name' => $item['game_name'] ?? null,
                    'tag_id' => $tag_id,
                    'ref_id' => $ref_id,
                    'ref_link' => $ref_top_domain . $item['ref_link']
                ]
            );
            Log::info('拉取文章: ' . $ref_name . ' - ' . $title);
        });

        return 'vgtime fecth success';
    }
}
