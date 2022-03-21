<div>
    <table class="table table-hover mt-5">
        <thead>
          <tr>
            <th scope="col">Title</th>
            <th scope="col">Writer</th>
            <th scope="col">Create</th>
            <th scope="col">좋아요 수</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($posts as $post)
          <tr>
            <td><a href="{{ route('posts.show', ['post' => $post->id]) }}" style="text-decoration:none; color:black">{{ $post->title }}</a></td>
              <td>{{ $post-> }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      {{ $posts->links() }}
</div>
