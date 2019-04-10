<?php
namespace Client\Controller;

class HonorController extends GlobalController
{

    protected $honor_m;
    protected $act_m;

    public function _initialize(){
        parent::_initialize();
        $this->honor_m = D('Client/Honor');
        $this->act_m = D('Client/Act');
    }


    public function showInfo(){
        $raw = $this->RxData;
        $uid = $this->out['uid'];
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'num' => 0, 'data' => [] ];

        $page_start = ( !isset( $raw[ 'page_start' ] ) || !is_numeric( $raw[ 'page_start' ] ) )?'1':$raw[ 'page_start' ];
        $page_limit = ( !isset( $raw[ 'page_limit' ] ) || !is_numeric( $raw[ 'page_limit' ] ) )?C('PAGE_LIMIT'):$raw[ 'page_limit' ];
        $limit = ($page_start-1)*$page_limit.','.$page_limit;
        $ret['page_start'] = $page_start;

        if( isset( $raw['uid'] ) && is_numeric( $raw['uid'] ) )
            $uid = $raw['uid'];

        $num_res = $this->honor_m->findClientHonor( 'count(*) num', [ 'uid' => $uid, 'type' => HT_FIXED ] );
        if( $num_res )
            $ret['num'] = $num_res['num'];

        $total = $this->honor_m->findHonor( 'count(*) num' );
        if( $total )
            $ret['total'] = $total['num'];

        $col = 'a.id,a.name,a.descr,a.icon,b.id status';
        $honor_res = $this->honor_m->getHonorInfo( $col, '', 'b.id DESC,a.id ASC', $limit, [ 'uid' => $uid ] );

        foreach ($honor_res as $value) {
            $status = S_FALSE;
            if( $value['status'] ){
                $status = S_TRUE;
            }

            $value['icon'] = \Common\GetCompleteUrl( $value['icon'] );
            $value['status'] = $status;

            $ret['data'][] = $value;
        }
        $ret['page_n'] = count($ret['data']);

END:
        $this->retReturn( $ret );
    }


    public function showActInfo(){
        $raw = $this->RxData;
        $uid = $this->out['uid'];
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'num' => 0, 'data' => [] ];

        $page_start = ( !isset( $raw[ 'page_start' ] ) || !is_numeric( $raw[ 'page_start' ] ) )?'1':$raw[ 'page_start' ];
        $page_limit = ( !isset( $raw[ 'page_limit' ] ) || !is_numeric( $raw[ 'page_limit' ] ) )?C('PAGE_LIMIT'):$raw[ 'page_limit' ];
        $limit = ($page_start-1)*$page_limit.','.$page_limit;
        $ret['page_start'] = $page_start;

        if( isset( $raw['uid'] ) && is_numeric( $raw['uid'] ) )
            $uid = $raw['uid'];

        $total = $this->honor_m->getActHonorInfo( 'count(*) num', [ 'a.uid' => $uid ], 'a.atime' );
        if( $total ){
            $ret['total'] = $total[0]['num'];
            $ret['num'] = $total[0]['num'];
        }

        $col = 'b.id,b.honor name,b.honor_descr descr,b.honor_icon icon,b.honor_color color';
        $honor_res = $this->honor_m->getActHonorInfo( $col, [ 'a.uid' => $uid ], 'a.atime' );

        foreach ($honor_res as $value) {
            $value['icon'] = \Common\GetCompleteUrl( $value['icon'] );
            $ret['data'][] = $value;
        }
        $ret['page_n'] = count($ret['data']);

END:
        $this->retReturn( $ret );
    }



}