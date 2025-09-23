
{
  "data": {
    "event_type": "call.initiated",
    "id": "3dd9dc17-5ed3-40e6-8d17-ff25122d2ce2",
    "occurred_at": "2025-09-11T12:33:32.940810Z",
    "payload": {
      "call_control_id": "v3:vZdWG4JkwAHwEj4KBrAFeLprgbHYQVN8qt9rAhmO8fQRZ5ohsjz1jw",
      "call_leg_id": "876b7aec-8f0b-11f0-a6b2-02420a1f0b69",
      "call_session_id": "876b6c5a-8f0b-11f0-9f1e-02420a1f0b69",
      "calling_party_type": "sip",
      "client_state": null,
      "connection_id": "2774993960299398855",
      "custom_headers": [
        {
          "name": "X-RTC-CALLID",
          "value": "163460b2-96a4-40ce-aaaf-31e9fd51d78f"
        },
        {
          "name": "X-RTC-SESSID",
          "value": "2225145f-36a7-498c-af6c-b4b22a94246d"
        },
        {
          "name": "X-rtc_leg_uuid",
          "value": "f88876a7-101e-4626-b882-61550ae79c10"
        }
      ],
      "direction": "outgoing",
      "from": "+12037206619",
      "from_sip_uri": "+12037206619@sip.telnyx.eu",
      "state": "bridging",
      "to": "+18004377950",
      "to_sip_uri": "+18004377950@sip.telnyx.eu"
    },
    "record_type": "event"
  },
  "meta": {
    "attempt": 1,
    "delivered_to": "https://webhook.site/75fe737a-3153-4a49-a59b-db4ab9120d1d"
  }
}
{
  "data": {
    "event_type": "call.answered",
    "id": "c4d8402a-1a28-4eb5-97a5-724adb4aa318",
    "occurred_at": "2025-09-11T12:34:01.931798Z",
    "payload": {
      "call_control_id": "v3:qDuJv_QDvrvhR2Ysh9wS5d5ivSCoNVfxDkV3v-iObXtgDv_rIkTGGw",
      "call_leg_id": "97125ac4-8f0b-11f0-bc4c-02420a1f0a69",
      "call_session_id": "9712493a-8f0b-11f0-b7cd-02420a1f0a69",
      "calling_party_type": "sip",
      "client_state": null,
      "connection_id": "2774993960299398855",
      "custom_headers": [
        {
          "name": "X-RTC-CALLID",
          "value": "8ca2b0de-78bf-451e-bcb0-0430491dd76d"
        },
        {
          "name": "X-RTC-SESSID",
          "value": "8446b991-b0e0-4454-8d53-39c55301bdd8"
        },
        {
          "name": "X-rtc_leg_uuid",
          "value": "2b4933da-0b9f-415b-b776-06fa7103904a"
        }
      ],
      "from": "+12037206619",
      "start_time": "2025-09-11T12:33:59.011772Z",
      "to": "+18004377950"
    },
    "record_type": "event"
  },
  "meta": {
    "attempt": 1,
    "delivered_to": "https://webhook.site/75fe737a-3153-4a49-a59b-db4ab9120d1d"
  }
}
{
  "data": {
    "event_type": "call.bridged",
    "id": "2c25ef5e-bb1a-4847-8011-7e738a9c8a20",
    "occurred_at": "2025-09-11T12:33:35.820802Z",
    "payload": {
      "call_control_id": "v3:vZdWG4JkwAHwEj4KBrAFeLprgbHYQVN8qt9rAhmO8fQRZ5ohsjz1jw",
      "call_leg_id": "876b7aec-8f0b-11f0-a6b2-02420a1f0b69",
      "call_session_id": "876b6c5a-8f0b-11f0-9f1e-02420a1f0b69",
      "calling_party_type": "sip",
      "client_state": null,
      "connection_id": "2774993960299398855",
      "from": "+12037206619",
      "start_time": "2025-09-11T12:33:32.760802Z",
      "to": "+18004377950"
    },
    "record_type": "event"
  },
  "meta": {
    "attempt": 1,
    "delivered_to": "https://webhook.site/75fe737a-3153-4a49-a59b-db4ab9120d1d"
  }
}

{
  "data": {
    "event_type": "call.hangup",
    "id": "f95c9c17-8dd9-4360-acc1-de74a6c2027a",
    "occurred_at": "2025-09-11T12:33:37.840802Z",
    "payload": {
      "call_control_id": "v3:vZdWG4JkwAHwEj4KBrAFeLprgbHYQVN8qt9rAhmO8fQRZ5ohsjz1jw",
      "call_leg_id": "876b7aec-8f0b-11f0-a6b2-02420a1f0b69",
      "call_quality_stats": {
        "inbound": {
          "jitter_max_variance": "0.00",
          "jitter_packet_count": "0",
          "mos": "4.50",
          "packet_count": "0",
          "skip_packet_count": "101"
        },
        "outbound": {
          "packet_count": "98",
          "skip_packet_count": "0"
        }
      },
      "call_session_id": "876b6c5a-8f0b-11f0-9f1e-02420a1f0b69",
      "calling_party_type": "sip",
      "client_state": null,
      "connection_id": "2774993960299398855",
      "custom_headers": [
        {
          "name": "X-RTC-CALLID",
          "value": "163460b2-96a4-40ce-aaaf-31e9fd51d78f"
        },
        {
          "name": "X-RTC-SESSID",
          "value": "2225145f-36a7-498c-af6c-b4b22a94246d"
        },
        {
          "name": "X-rtc_leg_uuid",
          "value": "f88876a7-101e-4626-b882-61550ae79c10"
        }
      ],
      "end_time": "2025-09-11T12:33:37.840802Z",
      "from": "+12037206619",
      "hangup_cause": "normal_clearing",
      "hangup_source": "caller",
      "sip_hangup_cause": "503",
      "start_time": "2025-09-11T12:33:32.760802Z",
      "to": "+18004377950"
    },
    "record_type": "event"
  },
  "meta": {
    "attempt": 1,
    "delivered_to": "https://webhook.site/75fe737a-3153-4a49-a59b-db4ab9120d1d"
  }
}
