<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PublicFlightSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $trip = (string) $this->input('trip_type', '');

        $base = [
            'trip_type' => ['required', Rule::in(['one_way', 'round_trip', 'multi_city'])],
            'cabin' => ['required', Rule::in(['economy', 'premium_economy', 'business', 'first'])],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'children' => ['sometimes', 'integer', 'min:0', 'max:8'],
            'infants' => ['sometimes', 'integer', 'min:0', 'max:9'],
        ];

        if ($trip === 'multi_city') {
            return array_merge($base, [
                'multi_from' => ['required', 'array', 'min:2', 'max:6'],
                'multi_from.*' => ['required', 'string', 'max:8'],
                'multi_to' => ['required', 'array', 'min:2', 'max:6'],
                'multi_to.*' => ['required', 'string', 'max:8'],
                'multi_depart' => ['required', 'array', 'min:2', 'max:6'],
                'multi_depart.*' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            ]);
        }

        $rules = array_merge($base, [
            'from' => ['required', 'string', 'max:8'],
            'to' => ['required', 'string', 'max:8'],
            'depart' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        if ($trip === 'round_trip') {
            $rules['return_date'] = ['required', 'date_format:Y-m-d', 'after_or_equal:depart'];
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()->route('flights.search')->withErrors($validator)->withInput()
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $adults = max(1, (int) $this->input('adults', 1));
            $children = max(0, (int) $this->input('children', 0));
            $infants = max(0, (int) $this->input('infants', 0));

            if ($infants > $adults) {
                $v->errors()->add('infants', __('Infants cannot exceed adults.'));
            }
            if ($adults + $children + $infants > 9) {
                $v->errors()->add('adults', __('A maximum of 9 passengers is allowed.'));
            }

            $trip = (string) $this->input('trip_type', '');
            if ($trip === 'multi_city') {
                $from = $this->input('multi_from', []);
                $to = $this->input('multi_to', []);
                $dep = $this->input('multi_depart', []);
                if (! is_array($from) || ! is_array($to) || ! is_array($dep)) {
                    return;
                }
                if (count($from) !== count($to) || count($from) !== count($dep)) {
                    $v->errors()->add('multi_from', __('Each segment needs From, To, and Date.'));

                    return;
                }
                $n = min(count($from), count($to), count($dep));
                if ($n < 2) {
                    $v->errors()->add('multi_from', __('Add at least two segments for multi-city.'));

                    return;
                }

                $today = now()->startOfDay();
                $prevDate = null;
                for ($i = 0; $i < $n; $i++) {
                    $ds = (string) ($dep[$i] ?? '');
                    try {
                        $dt = Carbon::parse($ds)->startOfDay();
                    } catch (\Throwable) {
                        continue;
                    }
                    if ($dt->lt($today)) {
                        $v->errors()->add('multi_depart.'.$i, __('Segment dates cannot be in the past.'));
                    }
                    if ($prevDate !== null && $dt->lt($prevDate)) {
                        $v->errors()->add('multi_depart.'.$i, __('Segments must be in chronological order.'));
                    }
                    $prevDate = $dt;
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function criteria(): array
    {
        $trip = (string) $this->input('trip_type', 'one_way');
        $adults = max(1, (int) $this->input('adults', 1));
        $children = max(0, (int) $this->input('children', 0));
        $infants = min(max(0, (int) $this->input('infants', 0)), $adults);

        $base = [
            'trip_type' => $trip,
            'cabin' => (string) $this->input('cabin', 'economy'),
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants,
        ];

        if ($trip === 'multi_city') {
            $segments = [];
            $from = $this->input('multi_from', []);
            $to = $this->input('multi_to', []);
            $dep = $this->input('multi_depart', []);
            if (! is_array($from) || ! is_array($to) || ! is_array($dep)) {
                return array_merge($base, [
                    'segments' => [],
                    'origin' => '',
                    'destination' => '',
                    'depart_date' => '',
                    'return_date' => null,
                ]);
            }
            $n = min(count($from), count($to), count($dep));
            for ($i = 0; $i < $n; $i++) {
                $segments[] = [
                    'origin' => strtoupper(trim((string) ($from[$i] ?? ''))),
                    'destination' => strtoupper(trim((string) ($to[$i] ?? ''))),
                    'departure_date' => (string) ($dep[$i] ?? ''),
                ];
            }

            return array_merge($base, [
                'segments' => $segments,
                'origin' => $segments[0]['origin'] ?? '',
                'destination' => $segments[0]['destination'] ?? '',
                'depart_date' => $segments[0]['departure_date'] ?? '',
                'return_date' => null,
            ]);
        }

        return array_merge($base, [
            'origin' => strtoupper(trim((string) $this->input('from', ''))),
            'destination' => strtoupper(trim((string) $this->input('to', ''))),
            'depart_date' => (string) $this->input('depart', ''),
            'return_date' => $trip === 'round_trip' ? (string) $this->input('return_date', '') : null,
        ]);
    }
}
