<?php
namespace Vonnda\Cognito\Model\Config\Source;

class UserPoolRegions implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            [
                'value' => 'us-east-1',
                'label' => 'US East (N. Virginia) - us-east-1'
            ],
            [
                'value' => 'us-east-2',
                'label' => 'US East (Ohio) - us-east-2'
            ],
            [
                'value' => 'us-west-2',
                'label' => 'US West (Oregon) - us-west-2'
            ],
            [
                'value' => 'ap-south-1',
                'label' => 'Asia Pacific (Mumbai) - ap-south-1'
            ],
            [
                'value' => 'ap-northeast-1',
                'label' => 'Asia Pacific (Tokyo) - ap-northeast-1'
            ],
            [
                'value' => 'ap-northeast-2',
                'label' => 'Asia Pacific (Seoul) - ap-northeast-2'
            ],
            [
                'value' => 'ap-southeast-1',
                'label' => 'Asia Pacific (Singapore) - ap-southeast-1'
            ],
            [
                'value' => 'ap-southeast-2',
                'label' => 'Asia Pacific (Sydney) - ap-southeast-2'
            ],
            [
                'value' => 'ca-central-1',
                'label' => 'Canada (Central) - ca-central-1'
            ],
            [
                'value' => 'eu-central-1',
                'label' => 'EU (Frankfurt) - eu-central-1'
            ],
            [
                'value' => 'eu-west-1',
                'label' => 'EU (Ireland) - eu-west-1'
            ],
            [
                'value' => 'eu-west-2',
                'label' => 'EU (London - eu-west-2)'
            ]
        ];
    }

    public function toArray()
    {
        return [
            'us-east-1' => 'US East (N. Virginia) - us-east-1',
            'us-east-2' => 'US East (Ohio) - us-east-2',
            'us-west-2' => 'US West (Oregon) - us-west-2',
            'ap-south-1' => 'Asia Pacific (Mumbai) - ap-south-1',
            'ap-northeast-1' => 'Asia Pacific (Tokyo) - ap-northeast-1',
            'ap-northeast-2' => 'Asia Pacific (Seoul) - ap-northeast-2',
            'ap-southeast-1' => 'Asia Pacific (Singapore) - ap-southeast-1',
            'ap-southeast-2' => 'Asia Pacific (Sydney) - ap-southeast-2',
            'ca-central-1' => 'Canada (Central) - ca-central-1',
            'eu-central-1' => 'EU (Frankfurt) - eu-central-1',
            'eu-west-1' => 'EU (Ireland) - eu-west-1',
            'eu-west-2' => 'EU (London) - eu-west-2'
        ];
    }

}