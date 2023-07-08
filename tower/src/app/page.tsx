'use client'

import useSWR from 'swr'

export default function Page() {
    const { data } = useSWR('http://localhost:8080/api/v1/hello', (api: string) => fetch(api).then(res => res.json()));

    // render data
    return <div>{data}</div>
}
