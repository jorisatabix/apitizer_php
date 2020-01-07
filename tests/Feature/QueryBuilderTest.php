<?php

namespace Tests\Feature;

use Tests\Feature\Builders\PostBuilder;
use Tests\Feature\Builders\UserBuilder;
use Tests\Feature\Models\User;
use Tests\Feature\Models\Post;
use Tests\Feature\Models\Comment;

class QueryBuilderTest extends TestCase
{
    /** @test */
    public function it_can_select_the_specified_fields()
    {
        $user = factory(User::class)->create();

        $request = $this->buildRequest(['fields' => 'id,name']);
        $results = UserBuilder::make($request)->all();

        $this->assertEquals([
            [
                'id' => $user->id,
                'name' => $user->name,
            ]
        ], $results);
    }

    /** @test */
    public function it_can_render_nested_selects()
    {
        $users = factory(User::class, 2)
               ->create()
               ->each(function (User $user) {
                   $posts = $user->posts()
                        ->saveMany(factory(Post::class, 2)->make()->all());

                   collect($posts)->each(function (Post $post) {
                       $comments = factory(Comment::class, 2)
                                 ->make(['author_id' => $post->author_id])
                                 ->all();
                       $post->comments()->saveMany($comments);
                   });
               });

        $request = $this->buildRequest(['fields' => 'id,name,posts(id,title,comments(id,body))']);
        $result = UserBuilder::make($request)->all();

        $expected = $users->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'posts' => $user->posts->map(function (Post $post) {
                    return [
                        'id' => $post->id,
                        'title' => $post->title,
                        'comments' => $post->comments->map(function (Comment $comment) {
                            return [
                                'id' => $comment->id,
                                'body' => $comment->body,
                            ];
                        })->all(),
                    ];
                })->all(),
            ];
        })->all();

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_can_order_results()
    {
        $users = factory(User::class, 2)->create();

        $request = $this->buildRequest([
            'sort' => 'id.desc',
            'fields' => 'id,name'
        ]);
        $result = UserBuilder::make($request)->all();

        $expected = [
            [
                'id' => $users[1]->id,
                'name' => $users[1]->name,
            ],
            [
                'id' => $users[0]->id,
                'name' => $users[0]->name,
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function if_no_fields_are_selected_all_non_association_fields_are_returned()
    {
        $user = factory(User::class)->create();
        $request = $this->buildRequest();
        $result = UserBuilder::make($request)->all();

        $expected = [
            [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d'),
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_can_filter_on_associations()
    {
        $users = factory(User::class, 2)->create();
        $post = factory(Post::class)->make();
        $users->first()->posts()->save($post);

        $request = $this->buildRequest([
            'fields' => 'id',
            'filters' => ['posts' => [$post->id]]
        ]);
        $result = UserBuilder::make($request)->all();

        $this->assertEquals([
            [
                'id' => $users->first()->id,
            ]
        ], $result);
    }

    /** @test */
    public function selecting_an_association_without_specifying_fields_fetches_all_fields()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['author_id' => $user->id]);
        $comment = factory(Comment::class)->make(['author_id' => $user->id]);
        $post->comments()->save($comment);

        $request = $this->buildRequest(['fields' => 'id,comments']);
        $result = PostBuilder::make($request)->all();

        $this->assertEquals([
            [
                'id' => $post->id,
                'comments' => [
                    [
                        'id' => $comment->id,
                        'body' => $comment->body,
                    ]
                ]
            ]
        ], $result);
    }

    /** @test */
    public function it_can_select_from_belongs_to_relations()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['author_id' => $user->id]);

        $request = $this->buildRequest(['fields' => 'id, author(id)']);
        $result = PostBuilder::make($request)->all();

        $this->assertEquals([
            [
                'id' => $post->id,
                'author' => [
                    'id' => $user->id,
                ]
            ]
        ], $result);
    }

    /** @test */
    public function it_can_handle_unexpected_array_fields()
    {
        $user = factory(User::class)->create();

        $request = $this->buildRequest(['fields' => ['id', 'posts(id)', 'posts' => ['id']]]);
        $result = UserBuilder::make($request)->all();

        $this->assertEquals([
            [
                'id' => $user->id,
            ]
        ], $result);
    }
}
