<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;




class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @OA\Info(
     *      version="3.3.6",
     *      title="라라벨 api문서",
     *      description="api 문서",
     *      @OA\Contact(
     *          email="orbi98@g.yju.ac.kr"
     *      ),
     *     @OA\License(
     *         name="Apache 2.0",
     *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
     *     )
     * )
     */

    /**
     *  @OA\Server(
     *      url=L5_SWAGGER_CONST_TEST_HOST,
     *      description="테스트 서버"
     *  )
     *
     */
}
