---
title: "AWS 비용 이상 알림 설정, Budgets와 Cost Anomaly Detection 무엇부터 걸까"
slug: "aws-cost-anomaly-detection-budgets-alerts"
author: "감성개발자"
date: "2026-11-28"
summary: "AWS 요금 폭탄을 청구서보다 먼저 알아채려면 경보가 두 종류 필요합니다. 정한 금액을 넘었는지 보는 AWS Budgets와 평소 패턴에서 벗어났는지 보는 Cost Anomaly Detection의 차이, 임계값 잡는 기준, 이메일·SNS·Slack 수신 채널 구성, 연말 점검 순서를 공식 문서 기준으로 짚습니다."
oneLineSummary: "예산 경보는 선을 넘었는지 묻고, 이상 탐지는 평소와 다른지 묻습니다. 둘 다 걸어 두되 받는 사람부터 정해야 울립니다."
tags: ["AWS", "클라우드", "인프라", "체크리스트", "Slack"]
status: "published"
---

이번 달 AWS 요금이 평소보다 많이 나온다는 사실을, 여러분 팀은 보통 언제 알게 되나요? 청구서를 받은 날인가요, 아니면 AWS 비용 이상 알림 설정을 해 둔 덕분에 그 전에 알았나요?

월초에 청구서를 열어 보고서야 아는 팀이 생각보다 많습니다. 테스트로 띄운 리소스가 한 달 내내 돌고 있었다는 걸 다음 달 청구서에서 발견하는 식입니다. 이럴 때 흔히 나오는 말이 "알림 같은 거 안 걸어 놨어?"인데, 막상 확인해 보면 알림이 걸려 있는 경우도 많습니다. 다만 아무도 보지 않는 메일 주소로 가고 있었을 뿐입니다. 여러 팀의 AWS 계정을 넘겨받아 보면 이 패턴이 의외로 자주 보입니다.

이 글은 그런 상황을 피하기 위한 AWS 비용 이상 알림 설정 기준입니다. AWS에는 성격이 다른 경보가 두 가지 있습니다. 정한 금액을 넘었는지 보는 **AWS Budgets**와, 평소 쓰던 패턴에서 벗어났는지 보는 **AWS Cost Anomaly Detection**입니다. 두 경보가 각각 무엇을 잡고 무엇을 놓치는지, 임계값은 어떤 기준으로 잡는지, 알림을 이메일·SNS·Slack 중 어디로 받을지, 그리고 연말에 어떤 순서로 점검할지를 다룹니다. 수치와 동작 방식은 이번에 AWS Cost Management 사용자 가이드와 AWS CLI 레퍼런스를 다시 열어 확인한 내용만 옮겼습니다.

예전에 쓴 [AWS 비용 절감 실전 가이드](/posts/aws-cost-optimization-checklist/)가 이미 새고 있는 돈을 찾는 글이었다면, 이 글은 새기 시작한 순간을 빨리 알아채는 쪽에 가깝습니다.

![책상 앞 개발자가 한도 선에 달린 종과 급등 구간이 표시된 그래프에 달린 종, 두 경보를 바라보는 일러스트](/images/tistory/aws-cost-anomaly-detection-budgets-alerts/1.jpg)

<p style="font-size:13px;color:#888;text-align:center;">일러스트: 한도 선에 달린 종과 평소와 다른 급등을 잡는 종, 성격이 다른 두 경보</p>

## 두 경보는 서로 다른 질문을 합니다

Budgets와 Cost Anomaly Detection은 둘 다 "요금 알림"으로 불리지만, 던지는 질문이 다릅니다.

Budgets는 **"내가 정한 선을 넘었나?"**를 묻습니다. 월 100달러로 예산을 잡고 80%에 알림을 걸면, 실제 비용이 80달러를 넘는 순간 알려 줍니다. 실제 발생 비용(ACTUAL)뿐 아니라 이번 달 예상 비용(FORECASTED)에도 알림을 걸 수 있습니다. 기준선을 사람이 정하기 때문에 결과가 예측 가능하고, 회계나 경영진에게 설명하기도 쉽습니다.

Cost Anomaly Detection은 **"평소와 다른가?"**를 묻습니다. 공식 문서 설명으로는 머신러닝 모델이 지난 지출 패턴을 학습해 이상 지출을 찾아내고, 주간·월간 계절성과 자연스러운 성장 추세까지 고려해 오탐을 줄입니다. 사람이 선을 정하지 않아도 "평소 하루 3달러 나가던 서비스가 오늘 30달러 나갔다"는 식의 변화를 잡아냅니다. 총액이 예산 안에 있어도 특정 서비스 하나가 튀면 알려 준다는 점이 Budgets와 가장 다른 부분입니다.

반대로 각자 못 보는 곳도 있습니다. Budgets는 예산 안에서 일어나는 급등을 보지 못합니다. 월 예산이 넉넉하면 월 중순에 시작된 사고가 예산선에 닿을 때까지 조용합니다. Cost Anomaly Detection은 천천히 늘어나는 비용에 약합니다. 매달 조금씩 늘어나는 비용은 모델 입장에서 "자연스러운 성장"으로 보일 수 있기 때문입니다. 그래서 저는 둘 중 하나를 고르는 문제가 아니라, 둘이 서로의 빈 곳을 덮게 거는 문제로 봅니다.

| 항목 | AWS Budgets | Cost Anomaly Detection |
|---|---|---|
| 판단 기준 | 사람이 정한 금액·비율 | 지난 패턴 대비 이상 여부(ML) |
| 잘 잡는 것 | 월 총액 초과, 예상 초과 | 특정 서비스·계정의 갑작스러운 급등 |
| 놓치기 쉬운 것 | 예산 안에서 일어난 급등 | 천천히 늘어나는 비용 |
| 데이터 갱신 | 하루 최대 3회(보통 8~12시간 간격) | 청구 데이터 처리 후 하루 약 3회 실행 |
| 첫 감지까지 | 예측 알림은 약 5주 사용 이력 필요 | 새 모니터 24시간, 새로 쓰는 서비스는 10일 이력 필요 |
| 알림 채널 | 이메일(알림당 최대 10개), SNS 토픽 1개 | 즉시 알림은 SNS, 일간·주간 요약은 이메일 |
| 비용 | 예산 모니터링·알림 무료, 액션 붙은 예산은 월 2개까지 무료 후 개당 하루 0.10달러 | 기본 구성은 비용 없이 제공(FAQ 기준) |
| Marketplace 요금 | 비용 예산에 포함 | Bedrock 서드파티 모델 외에는 감시하지 않음 |

표에서 놓치면 안 되는 줄이 하나 있습니다. 두 경보 모두 **실시간이 아니라는 점**입니다. Budgets 문서는 리소스를 쓴 시점과 청구되는 시점 사이에 지연이 있어서, 알림을 받기 전에 이미 임계값을 넘는 비용이 쌓일 수 있다고 분명히 적어 둡니다. Cost Anomaly Detection도 Cost Explorer 데이터를 쓰기 때문에 사용이 일어난 뒤 이상을 감지하기까지 최대 24시간이 걸릴 수 있습니다. 비용 알림은 사고를 막는 장치가 아니라, 사고를 하루 안에 알아채게 해 주는 장치라고 생각하는 편이 맞습니다.

## 이상 탐지는 이미 켜져 있을 수도 있습니다

Cost Anomaly Detection부터 확인해야 하는 이유가 있습니다. AWS FAQ에 따르면 2023년 3월 27일 이후 Cost Explorer를 처음 켠 결제(Payer) 계정과 일반 단독 계정에는 Cost Anomaly Detection이 자동으로 구성됩니다. 기본 구성은 이렇습니다.

- 모니터: 사용 중인 모든 AWS 서비스를 보는 AWS services 모니터 1개
- 알림: 일간 요약 이메일
- 받는 사람: 계정의 **루트 이메일**
- 임계값: 모델이 예상한 지출보다 40% 넘게 많고, 동시에 금액으로 100달러를 넘는 이상

여기서 문제는 받는 사람입니다. 루트 이메일은 계정을 만든 사람의 개인 메일이거나, 오래전에 만든 공용 주소인 경우가 많습니다. 앞에서 말한 "알림은 걸려 있었는데 아무도 못 봤다"가 대개 이 상황입니다. 콘솔에서 Billing and Cost Management → Cost Anomaly Detection으로 들어가 모니터와 알림 구독이 이미 있는지, 있다면 누구에게 가는지부터 보세요. 새로 만드는 것보다 받는 사람을 바꾸는 일이 먼저입니다.

반대로 이전부터 Cost Explorer를 쓰던 계정이라면 자동 구성 대상이 아니어서 모니터가 아예 없을 수 있습니다. 이 경우에는 직접 만들어야 합니다.

![왼쪽은 막대가 울타리 선을 넘으려는 장면, 오른쪽은 잔잔한 선의 급등 구간을 돋보기로 살피는 장면을 나란히 놓은 일러스트](/images/tistory/aws-cost-anomaly-detection-budgets-alerts/2.jpg)

<p style="font-size:13px;color:#888;text-align:center;">일러스트: 정한 선을 넘는지 보는 경보와 평소와 다른 움직임을 찾는 경보</p>

## AWS Budgets 설정, 임계값은 이렇게 잡습니다

Budgets 임계값에서 먼저 정할 것은 예산 금액 자체입니다. 저는 최근 3개월 청구액 가운데 가장 높았던 달을 기준으로 잡고, 거기에 약간의 여유를 더합니다. 평균으로 잡으면 정상적인 달에도 알림이 울려서 금방 무시하게 됩니다. 알림은 울려야 할 때만 울려야 계속 읽힙니다.

알림은 세 개를 기본값으로 둡니다.

1. **실제 비용 80%**: 월말 전에 여유가 있을 때 알게 하는 경고
2. **실제 비용 100%**: 예산을 넘었다는 확정 알림
3. **예상 비용 100%**: 이번 달 이 속도면 넘는다는 조기 경보

이렇게 나누는 이유는 두 알림이 동작하는 방식이 다르기 때문입니다. Budgets 모범 사례 문서에 따르면 실제 비용 알림은 예산 기간마다 임계값에 처음 도달했을 때 **한 번만** 발송됩니다. 반면 예측 알림은 예상치가 임계값을 넘었다가 내려가고 다시 넘으면 같은 기간에도 여러 번 올 수 있습니다. 그리고 예측 알림은 약 5주 분량의 사용 데이터가 쌓여야 동작하므로, 새 계정에서는 한동안 실제 비용 알림만 믿어야 합니다.

비율 임계값이 계산되는 방식도 알아 두면 좋습니다. CLI 레퍼런스의 예시대로 예산이 200달러이고 비율 임계값이 80%이면 160달러를 넘을 때 알림이 옵니다. 금액으로 직접 걸고 싶다면 `ThresholdType`을 `ABSOLUTE_VALUE`로 바꾸면 됩니다.

콘솔에서 만들어도 되지만, 계정이 여러 개라면 CLI로 같은 설정을 반복하는 편이 실수가 적습니다. 아래는 월 300달러 비용 예산에 위 세 알림을 거는 예시입니다. 구조는 AWS CLI `create-budget` 레퍼런스 예시를 따랐고, 계정 ID와 주소는 바꿔서 쓰면 됩니다.

`budget.json`

```json
{
  "BudgetName": "monthly-total",
  "BudgetType": "COST",
  "TimeUnit": "MONTHLY",
  "BudgetLimit": { "Amount": "300", "Unit": "USD" }
}
```

`notifications.json`

```json
[
  {
    "Notification": {
      "NotificationType": "ACTUAL",
      "ComparisonOperator": "GREATER_THAN",
      "Threshold": 80,
      "ThresholdType": "PERCENTAGE"
    },
    "Subscribers": [
      { "SubscriptionType": "EMAIL", "Address": "infra-alerts@example.com" }
    ]
  },
  {
    "Notification": {
      "NotificationType": "ACTUAL",
      "ComparisonOperator": "GREATER_THAN",
      "Threshold": 100,
      "ThresholdType": "PERCENTAGE"
    },
    "Subscribers": [
      { "SubscriptionType": "EMAIL", "Address": "infra-alerts@example.com" },
      { "SubscriptionType": "SNS", "Address": "arn:aws:sns:us-east-1:123456789012:cost-alerts" }
    ]
  },
  {
    "Notification": {
      "NotificationType": "FORECASTED",
      "ComparisonOperator": "GREATER_THAN",
      "Threshold": 100,
      "ThresholdType": "PERCENTAGE"
    },
    "Subscribers": [
      { "SubscriptionType": "EMAIL", "Address": "infra-alerts@example.com" }
    ]
  }
]
```

```bash
aws budgets create-budget \
  --account-id 123456789012 \
  --budget file://budget.json \
  --notifications-with-subscribers file://notifications.json
```

`TimePeriod`를 생략하면 시작일은 이번 달 1일(UTC), 종료일은 2087년 6월 15일로 들어갑니다. 공식 문서도 특정 기간 예산보다 반복 예산을 권하는데, 기간 예산은 종료일이 지나면 알림이 조용히 끊기기 때문입니다.

## Cost Anomaly Detection 임계값은 금액과 비율을 같이 봅니다

이상 탐지에서는 두 가지를 정합니다. 무엇을 볼지(모니터)와, 어느 정도의 이상부터 알릴지(알림 구독의 임계값)입니다.

모니터는 소규모 팀이라면 **AWS 관리형 AWS services 모니터 하나**로 충분합니다. 사용하는 모든 서비스를 각각 따로 평가하고, 새 서비스를 쓰기 시작하면 자동으로 포함합니다. 계정을 여러 개 운영하는 조직이라면 관리 계정에서 Linked account 관리형 모니터를 더하고, 환경 태그(`environment` 같은)를 비용 할당 태그로 활성화해 두었다면 태그 모니터도 고려할 만합니다. 다만 문서는 여러 차원에 걸친 모니터를 만들면 같은 이상이 중복 알림으로 올 수 있으니 피하라고 권합니다.

임계값은 두 종류입니다. **절대값**은 이상의 총 비용 영향(실제 지출 - 예상 지출)이 정한 금액 이상일 때, **비율**은 그 영향이 예상 지출 대비 정한 퍼센트 이상일 때 알립니다. 둘을 AND나 OR로 묶을 수 있습니다.

저는 둘을 AND로 묶는 쪽을 기본값으로 둡니다. 비율만 쓰면 평소 0.5달러 나가던 서비스가 2달러 나간 것도 300% 이상으로 잡혀서 알림이 지나치게 많아집니다. 금액만 쓰면 원래 큰 서비스의 작은 흔들림까지 걸립니다. AWS가 기본 구성에서 40%와 100달러를 함께 쓰는 것도 같은 이유로 보입니다. 금액 기준은 팀이 "이 정도면 오늘 확인해야 한다"고 느끼는 선으로 잡으면 됩니다. 월 청구액이 수백 달러인 팀과 수만 달러인 팀의 선은 당연히 다릅니다.

임계값 아래의 이상도 버려지지 않는다는 점은 알아 둘 만합니다. 알림이 오지 않았을 뿐, 모델이 찾은 이상은 모두 Detected anomalies 탭에 남고 기본 90일치를 볼 수 있습니다. 월간 점검 때 이 탭을 한 번 훑어보면 임계값을 너무 높게 잡았는지 판단할 수 있습니다. 그리고 이상이 여러 날 이어지면 임계값을 넘는 동안 알림도 계속 옵니다. 같은 알림이 매일 온다면 원인이 아직 해결되지 않았다는 뜻입니다.

CLI로 만들면 이렇습니다. 모니터를 만든 뒤 돌려받은 ARN을 구독에 넣습니다. 임계값 표현식은 AWS CLI `create-anomaly-subscription` 레퍼런스에 실린 형식을 그대로 썼습니다.

```bash
# 1) AWS 관리형 서비스 모니터
aws ce create-anomaly-monitor --anomaly-monitor '{
  "MonitorName": "all-services",
  "MonitorType": "DIMENSIONAL",
  "MonitorDimension": "SERVICE"
}'

# 2) 즉시 알림 구독: 영향 50달러 이상 AND 예상 대비 30% 이상
aws ce create-anomaly-subscription --anomaly-subscription '{
  "SubscriptionName": "cost-anomaly-immediate",
  "MonitorArnList": ["arn:aws:ce::123456789012:anomalymonitor/모니터-ID"],
  "Subscribers": [
    { "Type": "SNS", "Address": "arn:aws:sns:us-east-1:123456789012:cost-alerts" }
  ],
  "Frequency": "IMMEDIATE",
  "ThresholdExpression": {
    "And": [
      { "Dimensions": { "Key": "ANOMALY_TOTAL_IMPACT_ABSOLUTE",
          "MatchOptions": ["GREATER_THAN_OR_EQUAL"], "Values": ["50"] } },
      { "Dimensions": { "Key": "ANOMALY_TOTAL_IMPACT_PERCENTAGE",
          "MatchOptions": ["GREATER_THAN_OR_EQUAL"], "Values": ["30"] } }
    ]
  }
}'
```

예전 예제에서 자주 보이는 `Threshold` 숫자 필드는 지금 레퍼런스에서 지원 중단(deprecated)으로 표시돼 있고, `ThresholdExpression`을 쓰라고 안내합니다. 50과 30은 예시일 뿐이니 팀 규모에 맞게 바꾸세요.

## 알림은 받는 곳이 정해져야 울립니다

![중앙의 구름 모양 종에서 봉투, 스마트폰, 단체 대화창으로 알림이 퍼져 세 명의 팀원 책상에 닿는 일러스트](/images/tistory/aws-cost-anomaly-detection-budgets-alerts/3.jpg)

<p style="font-size:13px;color:#888;text-align:center;">일러스트: 하나의 알림이 이메일·휴대폰·팀 채팅방으로 나뉘어 전달되는 모습</p>

채널을 고를 때 먼저 알아야 할 제약이 있습니다. Cost Anomaly Detection의 **즉시 알림(IMMEDIATE)은 SNS로만**, 일간·주간 요약은 이메일로만 받을 수 있습니다. 일간 요약은 전날 감지된 상위 10개 이상을 비용 영향 순으로 묶어 매일 00:00 UTC에 만들어지는데, 한국 시간으로는 오전 9시 무렵입니다. 출근길에 읽기 좋은 시간이지만, 새벽에 시작된 사고는 다음 날 아침에야 요약에 담깁니다. 빨리 알아야 한다면 즉시 알림과 SNS를 같이 설정해야 합니다.

제가 기본으로 잡는 구성은 이렇습니다.

- **이메일**: 개인 주소가 아니라 팀 메일링 리스트. 퇴사나 휴가로 알림이 끊기지 않게 합니다.
- **SNS 토픽 하나**: Budgets 100% 알림과 이상 탐지 즉시 알림을 같은 토픽으로 받습니다.
- **Slack**: 그 SNS 토픽을 Amazon Q Developer in chat applications에 연결해 팀 채널로 보냅니다. 공식 문서는 Slack 외에 Microsoft Teams와 Amazon Chime도 안내합니다.

SNS를 쓸 때 가장 많이 막히는 곳은 토픽 접근 정책입니다. Budgets는 `budgets.amazonaws.com`, Cost Anomaly Detection은 `costalerts.amazonaws.com`이 토픽에 게시할 수 있어야 합니다. 토픽 하나를 같이 쓰려면 두 서비스 모두 허용해야 합니다. 아래는 두 문서의 정책 예시를 합쳐 보기 쉽게 정리한 것입니다. 실제로는 정책 전체를 이걸로 바꾸지 말고, 토픽의 기존 접근 정책 `"Statement": [` 배열 안에 두 문장(Statement)만 추가하세요. 기존 문장을 지우면 토픽 소유자 권한까지 사라질 수 있습니다.

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "AllowBudgetsPublish",
      "Effect": "Allow",
      "Principal": { "Service": "budgets.amazonaws.com" },
      "Action": "SNS:Publish",
      "Resource": "arn:aws:sns:us-east-1:123456789012:cost-alerts",
      "Condition": {
        "StringEquals": { "aws:SourceAccount": "123456789012" },
        "ArnLike": { "aws:SourceArn": "arn:aws:budgets::123456789012:*" }
      }
    },
    {
      "Sid": "AllowCostAnomalyPublish",
      "Effect": "Allow",
      "Principal": { "Service": "costalerts.amazonaws.com" },
      "Action": "SNS:Publish",
      "Resource": "arn:aws:sns:us-east-1:123456789012:cost-alerts",
      "Condition": {
        "StringEquals": { "aws:SourceAccount": ["123456789012"] }
      }
    }
  ]
}
```

토픽 정책과 함께 확인할 것이 세 가지 더 있습니다. 첫째, Budgets용 SNS 토픽은 예산과 **같은 계정**에 있어야 합니다. 계정을 넘나드는 SNS는 지원하지 않습니다. 둘째, 토픽에 암호화(SSE)를 켰다면 KMS 키 정책에서 권한을 따로 열어 줘야 합니다. Budgets 문서의 문제 해결 항목에는 "The SNS topic is encrypted" 오류가 나면 추가 권한 없이는 동작하지 않는다고 나와 있습니다. 셋째, 이메일 구독은 **확인 링크를 눌러야** 활성화됩니다. SNS 콘솔의 Subscriptions에서 상태가 `PendingConfirmation`으로 남아 있으면 알림은 아무 데도 가지 않습니다. 설정은 끝났는데 알림이 한 번도 오지 않는다면 가장 먼저 의심할 곳이 여기입니다.

## 예산 액션으로 자동 차단까지 걸어야 할까

Budgets에는 알림 외에 **예산 액션**이 있습니다. 임계값을 넘으면 IAM 정책이나 서비스 제어 정책(SCP)을 적용하거나, 지정한 EC2·RDS 인스턴스를 멈추게 할 수 있습니다. 자동으로 실행하거나, 사람이 승인한 뒤 실행하게 고를 수 있습니다.

듣기에는 요금 폭탄을 원천 봉쇄하는 기능 같지만, 저는 운영 계정에는 자동 실행을 걸지 않는 편입니다. 비용이 튀는 이유가 사고가 아니라 실제 트래픽 증가일 수도 있는데, 그때 인스턴스를 멈추면 비용 문제가 장애로 바뀝니다. 모범 사례 문서에도 주의할 점이 하나 적혀 있습니다. Auto Scaling 그룹에 속한 EC2를 예산 액션으로 멈추면 Auto Scaling이 인스턴스를 다시 띄우거나 새로 만들기 때문에, 시작 구성이 쓰는 역할의 권한을 막는 두 번째 액션을 함께 걸지 않으면 효과가 없습니다.

그래서 기준을 이렇게 둡니다. 개발·실험용 계정에는 "새 리소스 생성을 막는 거부 정책"을 자동으로 적용하는 액션을 걸고, 운영 계정에는 승인 후 실행으로 두거나 알림만 둡니다. 액션이 붙은 예산은 월 2개까지 무료이고, 그다음부터는 예산 하나당 하루 0.10달러가 붙는다는 점도 계산에 넣어 두세요.

## AWS 비용 이상 알림 설정, 연말 점검은 이 순서로

![겨울 풍경 속 구불구불한 길 위 네 개의 깃발 지점을 체크리스트를 든 사람이 따라 걷는 일러스트](/images/tistory/aws-cost-anomaly-detection-budgets-alerts/4.jpg)

<p style="font-size:13px;color:#888;text-align:center;">일러스트: 연말 비용 점검을 네 개의 지점으로 나눠 차례로 지나가는 길</p>

12월은 비용 알림을 점검하기 좋은 달입니다. 연휴에 사람이 자리를 비우는 동안 켜 둔 리소스가 그대로 돌고, 새해 예산을 정하기 전에 올해 패턴을 돌아볼 수 있기 때문입니다. 저는 아래처럼 네 단계로 나눠 진행합니다.

**1단계, 이번 주: 누가 받는지 확인**
Cost Anomaly Detection에 모니터와 구독이 있는지 보고, 받는 사람이 루트 이메일이면 팀 주소로 바꿉니다. SNS 구독 중 `PendingConfirmation` 상태가 없는지 확인합니다. 이 단계에 30분이면 충분합니다.

**2단계, 12월 첫 주: 빈 곳 채우기**
월 총액 비용 예산과 세 알림(실제 80%·100%, 예상 100%)을 만듭니다. 이상 탐지 구독에 금액·비율 AND 임계값을 걸고, 즉시 알림을 SNS → Slack으로 연결합니다. 마지막으로 SNS 콘솔에서 모든 구독이 확인(Confirmed) 상태인지 봅니다.

**3단계, 연휴 전: 끌 것 끄기**
개발·스테이징 리소스 정리는 [AWS 비용 절감 실전 가이드](/posts/aws-cost-optimization-checklist/)의 점검 항목을 그대로 따라가면 됩니다. 연휴 동안 누가 알림을 받고 누가 대응할지도 이때 정해 둡니다. 알림이 와도 볼 사람이 없으면 설정한 의미가 없습니다.

**4단계, 1월 첫 청구서: 임계값 다시 보기**
Detected anomalies 탭을 열어 임계값 아래에서 잡힌 이상이 무엇이었는지 봅니다. 놓쳐서는 안 될 이상이 아래에 있었다면 임계값을 낮추고, 반대로 쓸모없는 알림이 많았다면 금액 기준을 올립니다. 새해 예산 금액도 이때 갱신합니다.

혹시 이상 탐지 알림이 평소와 전혀 다른 리전이나 처음 보는 서비스를 가리킨다면, 비용 문제가 아니라 계정 보안 문제일 수 있습니다. 그때는 예전에 정리한 [AWS 계정 해킹 시 복구 절차](/posts/aws/)부터 확인하는 편이 순서상 맞습니다.

비용 알림을 설정하는 데 드는 시간은 한 시간이 채 안 됩니다. 그런데 그 한 시간을 미루는 이유는 늘 "아직 문제가 없어서"입니다. 그리고 설정해 놓고도 받는 사람이 잘못 정해져 있으면 결국 청구서로 알게 되는 건 똑같습니다. 경보를 거는 것만큼 그 경보가 누구 휴대폰에서 울리는지 확인하는 일이 중요하다는 걸, 연말 정리 목록 맨 위에 적어 두셨으면 합니다.

## 참고한 공식 문서

- [AWS Cost Management User Guide — Managing your costs with AWS Budgets](https://docs.aws.amazon.com/cost-management/latest/userguide/budgets-managing-costs.html)
- [AWS Cost Management User Guide — Best practices for AWS Budgets](https://docs.aws.amazon.com/cost-management/latest/userguide/budgets-best-practices.html)
- [AWS Cost Management User Guide — Configuring budget actions](https://docs.aws.amazon.com/cost-management/latest/userguide/budgets-controls.html)
- [AWS Cost Management User Guide — Creating an Amazon SNS topic for budget notifications](https://docs.aws.amazon.com/cost-management/latest/userguide/budgets-sns-policy.html)
- [AWS Cost Management User Guide — Receiving budget alerts in chat applications](https://docs.aws.amazon.com/cost-management/latest/userguide/sns-alert-chime.html)
- [AWS Cost Management User Guide — Detecting unusual spend with AWS Cost Anomaly Detection](https://docs.aws.amazon.com/cost-management/latest/userguide/manage-ad.html)
- [AWS Cost Management User Guide — Getting started with AWS Cost Anomaly Detection](https://docs.aws.amazon.com/cost-management/latest/userguide/getting-started-ad.html)
- [AWS Cost Management User Guide — Creating an Amazon SNS topic for anomaly notifications](https://docs.aws.amazon.com/cost-management/latest/userguide/ad-SNS.html)
- [AWS Cost Anomaly Detection FAQs](https://aws.amazon.com/aws-cost-management/aws-cost-anomaly-detection/faqs/)
- [AWS Budgets Pricing](https://aws.amazon.com/aws-cost-management/aws-budgets/pricing/)
- [AWS CLI Command Reference — budgets create-budget](https://docs.aws.amazon.com/cli/latest/reference/budgets/create-budget.html)
- [AWS CLI Command Reference — ce create-anomaly-monitor](https://docs.aws.amazon.com/cli/latest/reference/ce/create-anomaly-monitor.html)
- [AWS CLI Command Reference — ce create-anomaly-subscription](https://docs.aws.amazon.com/cli/latest/reference/ce/create-anomaly-subscription.html)
