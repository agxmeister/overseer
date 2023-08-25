'use client'

import useSWR from 'swr'

import {default as TaskMap} from "@/components/Map/Map";
import Card from "@/components/Card/Card";
import React, {useState} from "react";
import Slot from "@/components/Slot/Slot";
import {getDates} from "@/utils/date";
import Marker, {MarkerPosition} from "@/components/Marker/Marker";
import Link from "@/components/Link/Link";
import Task from "@/components/Task/Task";
import Console from "@/components/Console/Console";
import {ApiUrl} from "@/constants/api";

type Issue = {
    key: string,
    estimatedStartDate: string,
    estimatedFinishDate: string,
    summary: string,
    links: {inward: Link[], outward: Link[]},
}
type Link = {
    key: string,
    type: string,
}

type LinkDescription = {
    start: string,
    finish: string,
}

export default function Page()
{
    const [scale, setScale] = useState<number>(1);
    const handleScaleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
        if (['=', '+'].includes(event.key)) {
            setScale(scale + 0.1);
        } else if (['-', '_'].includes(event.key)) {
            setScale(scale - 0.1);
        }
    };

    const dates = getDates(new Date("2023-08-01"), new Date("2023-08-31"));

    const [sizeTaskId, setSizeTaskId] = useState<string|null>(null);
    const onSize = (taskId: string) => {
        setSizeTaskId(taskId);
    }

    const [url, setUrl] = useState(ApiUrl.TASKS);

    const {data, mutate} = useSWR(url, (api: string) => fetch(api).then(res => res.json()));
    const onMutate = (fetcher: Function, mutation: {taskId: string, direction: string, date: string}) => {
        const optimisticData = data.map((issue: Issue) =>
            issue.key === mutation.taskId ?
                (mutation.direction === MarkerPosition.Left ?
                    {...issue, estimatedStartDate: mutation.date} :
                    {...issue, estimatedFinishDate: mutation.date}) :
                issue);
        mutate(fetcher,{
            optimisticData: optimisticData,
            populateCache: (mutatedIssue, issues) => {
                return issues.map((issue: Issue) => issue.key === mutatedIssue.key ? mutatedIssue : issue);
            },
            revalidate: false
        });
    }
    const onLink = (fetcher: Function) => {
        mutate(fetcher,{
            populateCache: false,
        });
    }

    const links = Array.from<[string, LinkDescription]>(data ? data.reduce((acc: Map<string, LinkDescription>, issue: Issue) => {
        Object.entries(issue.links).reduce((acc, [type, links]) => {
            links.reduce((acc, link) => {
                const key = type === 'outward' ? `${issue.key}-${link.key}` : `${link.key}-${issue.key}`;
                acc.set(key, {
                    start: type === 'outward' ? issue.key : link.key,
                    finish: type === 'outward' ? link.key : issue.key,
                });
                return acc;
            }, acc);
            return acc;
        }, acc);
        return acc;
    }, new Map<string, LinkDescription>()) : new Map<string, LinkDescription>())
        .map(([key, link]: [string, LinkDescription]) => (
            <Link
                key={key}
                startMarkerId={link.start}
                finishMarkerId={link.finish}
            />
        ));

    const tasks = data ? data.map((issue: Issue) =>
        <Task
            key={issue.key}
            id={issue.key}
            markerLeft={
                <Marker
                    id={issue.key}
                    position={MarkerPosition.Left}
                    onSize={onSize}
                />
            }
            markerRight={
                <Marker
                    id={issue.key}
                    position={MarkerPosition.Right}
                    onSize={onSize}
                />
            }
            start={issue.estimatedStartDate}
            finish={issue.estimatedFinishDate}
            card={
                <Card
                    key={issue.key}
                    id={issue.key}
                    title={issue.summary}
                />
            }
            onLink={onLink}
        />
    ): [];

    const slots = sizeTaskId !== null ? dates
        .map(date =>
            <Slot
                key={date}
                id={sizeTaskId}
                position={date}
                onMutate={onMutate}
            />
        ) : [];

    return (
        <>
            <div tabIndex={0} onKeyDown={handleScaleKeyDown}>
                <TaskMap
                    scale={scale}
                    dates={dates}
                    tasks={tasks}
                    slots={slots}
                    links={links}
                />
            </div>
            <div>
                <Console setScale={setScale} setUrl={setUrl}/>
            </div>
        </>
    );
}
