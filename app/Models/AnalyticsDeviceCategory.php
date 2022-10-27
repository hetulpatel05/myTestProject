<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDeviceCategory extends Model
{
    use HasFactory;

    protected $table="analytics_device_category";

    public static function getDeviceData($uid,$campaign_id)
    {
        $analytics_device_data=AnalyticsDeviceCategory::join('user_analytics','user_analytics.id','analytics_device_category.analytics_user_id')
        ->where('user_analytics.user_id',$uid)
        ->where('user_analytics.campaign_id',$campaign_id)
        ->select('analytics_device_category.desktop','analytics_device_category.mobile','analytics_device_category.tablet')
        ->first();

        if(!empty($analytics_device_data))
        {
            $total_devices=$analytics_device_data['desktop']+$analytics_device_data['mobile']+$analytics_device_data['tablet'];

            $desktop_percentage=number_format(($analytics_device_data['desktop']/$total_devices)*100,2);
            $mobile_percentage=number_format(($analytics_device_data['mobile']/$total_devices)*100,2);
            $tablet_percentage=number_format(($analytics_device_data['tablet']/$total_devices)*100,2);

            $desktop_data['name']='Desktop';
            $desktop_data['y']=(double)$desktop_percentage;
            $desktop_data['color']="#2c5ccb";

            $mobile_data['name']='Mobile';
            $mobile_data['y']=(double)$mobile_percentage;
            $mobile_data['color']="#3979f1";

            $tablet_data['name']='Tablet';
            $tablet_data['y']=(double)$tablet_percentage;
            $tablet_data['color']="#7faaf7";

            $chart_data[]=$desktop_data;
            $chart_data[]=$mobile_data;
            $chart_data[]=$tablet_data;
            
            return $chart_data;
        }
    }
}
